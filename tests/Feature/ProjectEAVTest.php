<?php

use App\Models\Project;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Faker\Factory as Faker;
use App\Enums\ProjectStatus;
use App\Enums\AttributeType;

uses(RefreshDatabase::class);

/** @test */
it('retrieves a dynamic project attribute', function () {
    $faker = Faker::create();

    $project = Project::create([
        'name' => $faker->company,
        'status' => $faker->randomElement(ProjectStatus::values()),
    ]);

    $departmentAttribute = Attribute::create([
        'name' => 'department',
        'type' => AttributeType::TEXT,
    ]);

    $departmentValue = $faker->word;
    
    AttributeValue::create([
        'attribute_id' => $departmentAttribute->id,
        'entity_id' => $project->id,
        'value' => $departmentValue,
    ]);

    $fetchedProject = Project::find($project->id);
    expect($fetchedProject->department)->toBe($departmentValue);
});

/** @test */
it('returns null for an undefined attribute', function () {
    $faker = Faker::create();
    
    $project = Project::create([
        'name' => $faker->company,
        'status' => $faker->randomElement(ProjectStatus::values()),
    ]);

    expect($project->department)->toBeNull();
});

/** @test */
it('updates a dynamic project attribute', function () {
    $faker = Faker::create();

    $project = Project::create([
        'name' => $faker->company,
        'status' => $faker->randomElement(ProjectStatus::values()),
    ]);

    $attribute = Attribute::create([
        'name' => 'department',
        'type' => AttributeType::TEXT,
    ]);

    $initialValue = $faker->word . ' Department';
    $updatedValue = $faker->word . ' Division';

    $value = AttributeValue::create([
        'attribute_id' => $attribute->id,
        'entity_id' => $project->id,
        'value' => $initialValue,
    ]);

    expect($project->department)->toBe($initialValue);

    $value->update(['value' => $updatedValue]);

    $updatedProject = Project::find($project->id);
    expect($updatedProject->department)->toBe($updatedValue);
});