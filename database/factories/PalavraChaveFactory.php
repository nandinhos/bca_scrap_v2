<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PalavraChaveFactory extends Factory
{
    public function definition(): array
    {
        return [
            'palavra' => strtoupper($this->faker->unique()->word()),
            'cor' => ltrim($this->faker->hexColor(), '#'),
            'ativa' => false,
        ];
    }
}
