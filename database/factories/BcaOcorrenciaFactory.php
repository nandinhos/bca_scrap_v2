<?php

namespace Database\Factories;

use App\Models\Bca;
use App\Models\Efetivo;
use Illuminate\Database\Eloquent\Factories\Factory;

class BcaOcorrenciaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bca_id' => Bca::factory(),
            'efetivo_id' => Efetivo::factory(),
            'tipo_match' => $this->faker->randomElement(['NOME', 'SARAM', 'SARAM + NOME']),
            'quantidade' => $this->faker->numberBetween(1, 10),
            'snippet' => $this->faker->optional()->text(200),
            'enviado_em' => null,
        ];
    }

    public function enviado(): static
    {
        return $this->state(fn (array $attributes) => [
            'enviado_em' => now(),
        ]);
    }
}
