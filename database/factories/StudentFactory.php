<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => '$2y$10$Fj5NVJvcqGvQxnmOF9Zx9u7EpOf73wXIY7xjmV2QPnXKzj/ibSXv6', // nextuniversityadmin
            // 'userId' => \App\Models\User::where('role_id', '=', 1)->get()->random()->id,
            'phone' => $this->faker->phoneNumber(),
            'location' => $this->faker->address(),
            'company' => $this->faker->company(),
            'position' => Str::random(10),
            'field' => Str::random(2),
            'status' => 1,
        ];
    }
}
