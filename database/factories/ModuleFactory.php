<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            // 'courseId' => $this->faker->name(),
            'name' => $this->faker->name(),
            'description' => Str::random(20),
            // 'remarks' => Str::random(10),
            'status' => 1,
        ];
    }
}
