<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Project;
use Faker\Factory as Faker;
use Carbon\Carbon;

class AttributeValueSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $projects = Project::all();
        $attributes = Attribute::all();

        if ($projects->isEmpty() || $attributes->isEmpty()) {
            return;
        }

        foreach ($projects as $project) {
            foreach ($attributes as $attribute) {
                $value = match ($attribute->type) {
                    'text' => $faker->word,
                    'date' => Carbon::now()->subDays(rand(10, 100))->toDateString(),
                    'number' => rand(1, 100),
                    'select' => $faker->randomElement(['Option A', 'Option B', 'Option C']),
                    default => 'Unknown'
                };

                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'entity_id' => $project->id,
                    'value' => $value,
                ]);
            }
        }
    }
}