<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    DB::table('oauth_clients')->insert([
        'id' => 'abcd',
        'user_id' => null,
        'name' => 'Personal Access Client',
        'secret' => 'secret123', // Set your own value
        'provider' => 'users',
        'redirect' => 'http://localhost',
        'personal_access_client' => 1,
        'password_client' => 0,
        'revoked' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('oauth_personal_access_clients')->insert([
        'client_id' => 'abcd',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    config([
        'passport.personal_access_client.id' => 'abcd',
        'passport.personal_access_client.secret' => 'secret123',
    ]);
});

/** @test */
it('registers a new user successfully', function () {
    $faker = Faker::create();

    $email = $faker->unique()->safeEmail;

    $response = $this->postJson('/api/register', [
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'User registered successfully',
        ]);

    // Ensure user exists in the database
    $this->assertDatabaseHas('users', [
        'email' => $email,
    ]);
});

// /** @test */
it('fails to register with invalid data', function () {
    $response = $this->postJson('/api/register', [
        'first_name' => '',
        'last_name' => '',
        'email' => 'invalid-email',
        'password' => ''
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password']);
});

// /** @test */
it('logs in a registered user and returns a token', function () {
    $faker = Faker::create();

    $email = $faker->unique()->safeEmail;
    $password = 'password';

    User::factory()->create([
        'email' => $email,
        'password' => bcrypt($password),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $email,
        'password' => $password,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['token', 'user']);
});

/** @test */
it('fails to log in with incorrect credentials', function () {
    $faker = Faker::create();

    $email = $faker->unique()->safeEmail;

    User::factory()->create([
        'email' => $email,
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $email,
        'password' => 'incorrect-password',
    ]);

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthorized']);
});

/** @test */
it('logs out an authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('API Token')->accessToken;

    $response = $this->postJson('/api/logout', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logged out successfully']);
});
