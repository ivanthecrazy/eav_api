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
it('retrieves only the projects assigned to the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Passport::actingAs($user);

    $assignedProjects = Project::factory()->count(3)->create();
    foreach ($assignedProjects as $project) {
        $project->users()->attach($user);
    }

    $unassignedProjects = Project::factory()->count(2)->create();
    foreach ($unassignedProjects as $project) {
        $project->users()->attach($otherUser);
    }

    $response = $this->getJson('/api/projects');

    $response->assertStatus(200)
        ->assertJsonCount(3);

    foreach ($response->json() as $project) {
        expect(collect($assignedProjects->pluck('id')))->toContain($project['id']);
    }
});

/** @test */
it('fails to retrieve all projects without authentication', function () {
    $response = $this->getJson('/api/projects');

    $response->assertStatus(401);
});

/** @test */
it('creates a project and assigns the current user automatically', function () {
    $user = User::factory()->create();
    Passport::actingAs($user); // Authenticate as the user

    $response = $this->postJson('/api/projects', [
        'name' => 'Cybersecurity Upgrade',
        'status' => 1
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'project' => ['id', 'name', 'status', 'users']]);

    $project = Project::first();

    expect($project->users)->toHaveCount(1);
    expect($project->users->first()->id)->toBe($user->id);
});

/** @test */
it('creates a project and assigns additional users along with the current user', function () {
    $user = User::factory()->create();
    $additionalUsers = User::factory()->count(2)->create();
    Passport::actingAs($user);

    $response = $this->postJson('/api/projects', [
        'name' => 'Network Security Upgrade',
        'status' => 1,
        'user_ids' => $additionalUsers->pluck('id')->toArray()
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'project' => ['id', 'name', 'status', 'users']]);

    $project = Project::first();

    expect($project->users)->toHaveCount(3);
    expect($project->users->pluck('id'))->toContain($user->id);
    expect($project->users->pluck('id'))->toContain($additionalUsers[0]->id);
    expect($project->users->pluck('id'))->toContain($additionalUsers[1]->id);
});

/** @test */
it('prevents duplicate user assignments when creating a project', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->postJson('/api/projects', [
        'name' => 'Duplicate User Test',
        'status' => 1,
        'user_ids' => [$user->id] // The current user is manually added again
    ]);

    $response->assertStatus(201);

    $project = Project::first();

    expect($project->users)->toHaveCount(1); // Still should be only one user
    expect($project->users->first()->id)->toBe($user->id);
});

/** @test */
it('prevents unauthorized users from creating projects', function () {
    $response = $this->postJson('/api/projects', [
        'name' => 'Unauthorized Project',
        'status' => 1
    ]);

    $response->assertStatus(401);
});

/** @test */
it('validates project creation data', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->postJson('/api/projects', [
        'name' => '', // Invalid: required
        'status' => 'invalid_status', // Invalid: should be integer
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'status']);
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
    $user = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->create();
    $project->users()->attach($user);
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
    $user = User::factory()->create();
    Passport::actingAs($user);

    // ✅ Create a project and assign the user
    $project = Project::factory()->create();
    $project->users()->attach($user);

    $updateData = [
        'name' => 'Updated Project Name'
    ];

    $response = $this->putJson("/api/projects/{$project->id}", $updateData);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Project updated successfully');

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => $updateData['name']
    ]);
});

/** @test */
it('updates a project and its attributes if the user is assigned to it', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    // ✅ Create a project and assign the user
    $project = Project::factory()->create();
    $project->users()->attach($user);

    // ✅ Create an attribute and assign a value to the project
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

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated Project Name',
    ]);

    $this->assertDatabaseHas('attribute_values', [
        'attribute_id' => $department->id,
        'entity_id' => $project->id,
        'value' => 'Cybersecurity',
    ]);
});

/** @test */
it('fails to update a project with non-existing attributes', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->create();
    $project->users()->attach($user);

    $response = $this->putJson("/api/projects/{$project->id}", [
        'attributes' => [
            ['name' => 'non_existent_attribute', 'value' => 'Some Value']
        ]
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['attributes.0.name']);
});

/** @test */
it('prevents updating a project if the user is not assigned to it', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->create();
    $project->users()->attach($otherUser);

    $response = $this->putJson("/api/projects/{$project->id}", [
        'name' => 'Updated Project Name'
    ]);

    $response->assertStatus(404);

    $this->assertDatabaseHas('projects', ['id' => $project->id]);
});

/** @test */
it('deletes a project successfully if the user is assigned to it', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->create();
    $project->users()->attach($user);

    $response = $this->deleteJson("/api/projects/{$project->id}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Project deleted successfully']);

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});

/** @test */
it('prevents deletion of a project if the user is not assigned to it', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->create();
    $project->users()->attach($otherUser);

    $response = $this->deleteJson("/api/projects/{$project->id}");

    $response->assertStatus(404);

    $this->assertDatabaseHas('projects', ['id' => $project->id]);
});
