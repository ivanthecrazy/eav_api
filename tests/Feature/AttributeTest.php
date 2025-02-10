<?php

use App\Models\Attribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Faker\Factory as Faker;
use App\Enums\AttributeType;

uses(RefreshDatabase::class);

/** @test */
it('retrieves all attributes', function () {
    Attribute::factory()->count(15)->create();

    Passport::actingAs(User::factory()->create());

    $response = $this->getJson('/api/attributes');

    $response->assertStatus(200)
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('total', 15);
});

/** @test */
it('creates a new attribute', function () {
    Passport::actingAs(User::factory()->create());

    $response = $this->postJson('/api/attributes', [
        'name' => 'Priority',
        'type' => AttributeType::SELECT
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'attribute' => ['id', 'name', 'type']])
        ->assertJsonPath('attribute.name', 'Priority')
        ->assertJsonPath('attribute.type', AttributeType::SELECT->value);

    $this->assertDatabaseHas('attributes', [
        'name' => 'Priority',
        'type' => AttributeType::SELECT->value
    ]);
});

/** @test */
it('prevents creating an attribute with a duplicate name', function () {
    Attribute::factory()->create(['name' => 'Department']);

    Passport::actingAs(User::factory()->create());

    $response = $this->postJson('/api/attributes', [
        'name' => 'Department',
        'type' => AttributeType::TEXT
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

/** @test */
it('retrieves a single attribute', function () {
    Passport::actingAs(User::factory()->create());

    $attribute = Attribute::factory()->create();

    $response = $this->getJson("/api/attributes/{$attribute->id}");

    $response->assertStatus(200)
        ->assertJsonPath('id', $attribute->id)
        ->assertJsonPath('name', $attribute->name)
        ->assertJsonPath('type', $attribute->type->value);
});

/** @test */
it('updates an attribute', function () {
    Passport::actingAs(User::factory()->create());

    $attribute = Attribute::factory()->create();

    $response = $this->putJson("/api/attributes/{$attribute->id}", [
        'name' => 'Updated Name',
        'type' => AttributeType::NUMBER
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Attribute updated successfully')
        ->assertJsonPath('attribute.name', 'Updated Name')
        ->assertJsonPath('attribute.type', AttributeType::NUMBER->value);

    $this->assertDatabaseHas('attributes', [
        'id' => $attribute->id,
        'name' => 'Updated Name',
        'type' => AttributeType::NUMBER->value
    ]);
});

/** @test */
it('prevents updating an attribute with a duplicate name', function () {
    Passport::actingAs(User::factory()->create());

    Attribute::factory()->create(['name' => 'Existing Name']);
    $attribute = Attribute::factory()->create();

    $response = $this->putJson("/api/attributes/{$attribute->id}", [
        'name' => 'Existing Name'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

/** @test */
it('deletes an attribute', function () {
    Passport::actingAs(User::factory()->create());

    $attribute = Attribute::factory()->create();

    $response = $this->deleteJson("/api/attributes/{$attribute->id}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Attribute deleted successfully']);

    $this->assertDatabaseMissing('attributes', ['id' => $attribute->id]);
});