<?php

use App\Models\Timesheet;
use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Faker\Factory as Faker;

uses(RefreshDatabase::class);

/** @test */
it('retrieves only the authenticated user\'s timesheets with pagination', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->hasAttached($user)->create();

    // Create 15 timesheets for this user
    Timesheet::factory()->count(15)->for($user)->for($project)->create();

    // Create another user's timesheets
    Timesheet::factory()->count(5)->create();

    $response = $this->getJson('/api/timesheets?per_page=10');

    $response->assertStatus(200)
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('total', 15);
});

/** @test */
it('creates a timesheet for an assigned project', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->hasAttached($user)->create();

    $response = $this->postJson('/api/timesheets', [
        'task_name' => 'Fix Security Issues',
        'date' => now()->toDateString(),
        'hours' => 4.5,
        'project_id' => $project->id
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'timesheet' => ['id', 'task_name', 'date', 'hours', 'project']]);

    $this->assertDatabaseHas('timesheets', [
        'task_name' => 'Fix Security Issues',
        'user_id' => $user->id,
        'project_id' => $project->id
    ]);
});

/** @test */
it('prevents timesheet creation for a project where the user is not assigned', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->create(); // Project exists but user is not assigned

    $response = $this->postJson('/api/timesheets', [
        'task_name' => 'Unauthorized Task',
        'date' => now()->toDateString(),
        'hours' => 2,
        'project_id' => $project->id
    ]);

    $response->assertStatus(403) // Unauthorized
        ->assertJson(['message' => 'This action is unauthorized.']);
});

/** @test */
it('retrieves a single timesheet belonging to the authenticated user', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->hasAttached($user)->create();
    $timesheet = Timesheet::factory()->for($user)->for($project)->create();

    $response = $this->getJson("/api/timesheets/{$timesheet->id}");

    $response->assertStatus(200)
        ->assertJsonPath('id', $timesheet->id);
});

/** @test */
it('prevents retrieving another user\'s timesheet', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->hasAttached($otherUser)->create();
    $timesheet = Timesheet::factory()->for($otherUser)->for($project)->create();

    $response = $this->getJson("/api/timesheets/{$timesheet->id}");

    $response->assertStatus(404);
});

/** @test */
it('updates a timesheet that belongs to the authenticated user', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->hasAttached($user)->create();
    $timesheet = Timesheet::factory()->for($user)->for($project)->create();

    $response = $this->putJson("/api/timesheets/{$timesheet->id}", [
        'task_name' => 'Updated Task Name',
        'hours' => 5
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Timesheet updated successfully')
        ->assertJsonPath('timesheet.task_name', 'Updated Task Name')
        ->assertJsonPath('timesheet.hours', 5);

    $this->assertDatabaseHas('timesheets', [
        'id' => $timesheet->id,
        'task_name' => 'Updated Task Name',
        'hours' => 5
    ]);
});

/** @test */
it('prevents updating another user\'s timesheet', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->hasAttached($otherUser)->create();
    $timesheet = Timesheet::factory()->for($otherUser)->for($project)->create();

    $response = $this->putJson("/api/timesheets/{$timesheet->id}", [
        'task_name' => 'Unauthorized Update',
    ]);

    $response->assertStatus(403);
});

/** @test */
it('deletes a timesheet that belongs to the authenticated user', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->hasAttached($user)->create();
    $timesheet = Timesheet::factory()->for($user)->for($project)->create();

    $response = $this->deleteJson("/api/timesheets/{$timesheet->id}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Timesheet deleted successfully']);

    $this->assertDatabaseMissing('timesheets', ['id' => $timesheet->id]);
});

/** @test */
it('prevents deleting another user\'s timesheet', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Passport::actingAs($user);

    $project = Project::factory()->hasAttached($otherUser)->create();
    $timesheet = Timesheet::factory()->for($otherUser)->for($project)->create();

    $response = $this->deleteJson("/api/timesheets/{$timesheet->id}");

    $response->assertStatus(403);
});