<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attribute;
use App\Enums\AttributeType;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Attribute::create(['name' => 'department', 'type' => AttributeType::TEXT]);
        Attribute::create(['name' => 'start_date', 'type' => AttributeType::DATE]);
        Attribute::create(['name' => 'end_date', 'type' => AttributeType::DATE]);
    }
}
