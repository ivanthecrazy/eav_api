<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Project;
use Faker\Factory as Faker;
use Carbon\Carbon;

class TimesheetSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $users = User::all();
        $projects = Project::all();

        if ($users->isEmpty() || $projects->isEmpty()) {
            return;
        }

        foreach ($projects as $project) {
            foreach ($users as $user) {
                // Assign 3-5 random timesheet entries per user per project
                for ($i = 0; $i < rand(3, 5); $i++) {
                    Timesheet::create([
                        'task_name' => $faker->sentence(3),
                        'date' => Carbon::now()->subDays(rand(1, 30)), // Last 30 days
                        'hours' => rand(1, 8), // Between 1 and 8 hours
                        'user_id' => $user->id,
                        'project_id' => $project->id,
                    ]);
                }
            }
        }
    }
}