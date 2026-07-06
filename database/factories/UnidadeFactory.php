<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UnidadeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => $this->faker->company(),
            'sigla' => strtoupper($this->faker->unique()->lexify('???')),
            'codigo' => strtoupper($this->faker->unique()->lexify('???')),
            'ativo' => true,
        ];
    }

    public function inativa(): static
    {
        return $this->state(fn (array $attributes) => [
            'ativo' => false,
        ]);
    }
}
