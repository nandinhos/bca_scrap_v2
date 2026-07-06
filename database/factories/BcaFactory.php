<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BcaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero' => str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'data' => $this->faker->unique()->date(),
            'url' => null,
            'texto_completo' => 'BCA de teste '.$this->faker->text(200),
            'processado_em' => now(),
        ];
    }
}
