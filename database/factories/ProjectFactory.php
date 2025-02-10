<?php
namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'status' => $this->faker->randomElement(ProjectStatus::values())
        ];
    }

    /**
     * Assign users to the project.
     */
    public function withUsers($count = 2)
    {
        return $this->afterCreating(function (Project $project) use ($count) {
            $users = User::factory()->count($count)->create();
            $project->users()->attach($users);
        });
    }
}