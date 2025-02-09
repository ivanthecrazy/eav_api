<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\User;
use App\Enums\ProjectStatus;
use Faker\Factory as Faker;

class ProjectSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $users = User::all();

        if ($users->isEmpty()) {
            return; // Exit if no users exist
        }

        // Create multiple projects
        for ($i = 0; $i < 5; $i++) {
            $project = Project::create([
                'name' => $faker->company . ' Project',
                'status' => $faker->randomElement(ProjectStatus::values()),
            ]);

            // Assign 2-4 random users to this project
            $randomUsers = $users->random(rand(2, 4));
            $project->users()->attach($randomUsers);
        }
    }
}