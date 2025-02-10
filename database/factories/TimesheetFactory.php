<?php

namespace Database\Factories;

use App\Models\Timesheet;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Timesheet>
 */
class TimesheetFactory extends Factory
{
    protected $model = Timesheet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_name' => $this->faker->sentence(3), // Generates a random task name
            'date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'), // Random date within the last month
            'hours' => $this->faker->randomFloat(1, 0.5, 8), // Generates random hours between 0.5 and 8
            'user_id' => User::factory(), // Creates a new user if not provided
            'project_id' => Project::factory(), // Creates a new project if not provided
        ];
    }
}