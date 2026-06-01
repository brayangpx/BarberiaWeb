<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shared_id' => (string) Str::uuid(),
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('715#######'),
            'password' => 'password',
            'role' => 'barber',
            'remember_token' => Str::random(10),
        ];
    }
}
