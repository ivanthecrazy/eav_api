<?php

use App\Models\Project;
use App\Models\User;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Faker\Factory as Faker;

uses(RefreshDatabase::class);

/** @test */
it('retrieves all projects', function () {
    Project::factory()->count(3)->create();

    Passport::actingAs(User::factory()->create());

    $response = $this->getJson('/api/projects');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

/** @test */
it('fails to retrieve all projects without authentication', function () {
    $response = $this->getJson('/api/projects');

    $response->assertStatus(401);
});

/** @test */
it('creates a project with existing attributes', function () {
    $faker = Faker::create();
    $user = User::factory()->create();
    Passport::actingAs($user);

    // Create predefined attributes
    $department = Attribute::create(['name' => 'department', 'type' => 'text']);
    $startDate = Attribute::create(['name' => 'start_date', 'type' => 'date']);

    $projectData = [
        'name' => $faker->company,
        'status' => 1,
        'attributes' => [
            ['name' => 'department', 'value' => 'IT Security'],
            ['name' => 'start_date', 'value' => '2024-01-01'],
        ]
    ];

    $response = $this->postJson('/api/projects', $projectData);

    $response->assertStatus(201)
        ->assertJsonPath('message', 'Project created successfully')
        ->assertJsonStructure(['project' => ['id', 'name', 'status', 'attribute_values']]);

    // Ensure attributes are stored
    $this->assertDatabaseHas('attribute_values', [
        'attribute_id' => $department->id,
        'value' => 'IT Security',
    ]);

    $this->assertDatabaseHas('attribute_values', [
        'attribute_id' => $startDate->id,
        'value' => '2024-01-01',
    ]);
});

/** @test */
it('fails to create a project with non-existing attributes', function () {
    Passport::actingAs(User::factory()->create());

    $response = $this->postJson('/api/projects', [
        'name' => 'Security Project',
        'status' => 1,
        'attributes' => [
            ['name' => 'unknown_attribute', 'value' => 'Some Value']
        ]
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['attributes.0.name']);
});

/** @test */
it('retrieves a single project with attributes', function () {
    $faker = Faker::create();
    Passport::actingAs(User::factory()->create());

    $project = Project::factory()->create();
    $attribute = Attribute::create(['name' => 'region', 'type' => 'text']);

    $attribute_value = $faker->country();

    AttributeValue::create([
        'attribute_id' => $attribute->id,
        'entity_id' => $project->id,
        'value' => $attribute_value,
    ]);

    $response = $this->getJson("/api/projects/{$project->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['id', 'name', 'status', 'attribute_values'])
        ->assertJsonPath('attribute_values.0.attribute_name', 'region')
        ->assertJsonPath('attribute_values.0.value', $attribute_value);
});

/** @test */
it('updates a project without attributes', function () {
    Passport::actingAs(User::factory()->create());

    $project = Project::factory()->create();
    
    $updateData = [
        'name' => 'Updated Project Name'
    ];

    $response = $this->putJson("/api/projects/{$project->id}", $updateData);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Project updated successfully');

    $this->assertDatabaseHas('projects', [
        'name' => $updateData['name']
    ]);
});

/** @test */
it('updates a project and its attributes', function () {
    $faker = Faker::create();
    Passport::actingAs(User::factory()->create());

    $project = Project::factory()->create();
    $department = Attribute::create([
        'name' => 'department',
        'type' => 'text'
    ]);

    AttributeValue::create([
        'attribute_id' => $department->id,
        'entity_id' => $project->id,
        'value' => 'IT Security',
    ]);

    $updateData = [
        'name' => 'Updated Project Name',
        'attributes' => [
            ['name' => 'department', 'value' => 'Cybersecurity'],
        ]
    ];

    $response = $this->putJson("/api/projects/{$project->id}", $updateData);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Project updated successfully')
        ->assertJsonStructure(['project' => ['id', 'name', 'status', 'attribute_values']])
        ->assertJsonPath('project.attribute_values.0.value', 'Cybersecurity');

    $this->assertDatabaseHas('attribute_values', [
        'attribute_id' => $department->id,
        'entity_id' => $project->id,
        'value' => 'Cybersecurity',
    ]);
});

/** @test */
it('fails to update a project with non-existing attributes', function () {
    Passport::actingAs(User::factory()->create());

    $project = Project::factory()->create();

    $response = $this->putJson("/api/projects/{$project->id}", [
        'attributes' => [
            ['name' => 'non_existent_attribute', 'value' => 'Some Value']
        ]
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['attributes.0.name']);
});

/** @test */
it('deletes a project successfully', function () {
    Passport::actingAs(User::factory()->create());

    $project = Project::factory()->create();

    $response = $this->deleteJson("/api/projects/{$project->id}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Project deleted successfully']);

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});
