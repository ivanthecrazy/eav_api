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
        for ($i = 0; $i < 20; $i++) {
            $project = Project::create([
                'name' => $faker->company . ' Project',
                'status' => $faker->randomElement(ProjectStatus::values()),
            ]);

            $randomUsers = $users->random(rand(2, 10));
            $project->users()->attach($randomUsers);
        }
    }
}