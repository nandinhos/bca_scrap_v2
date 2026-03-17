<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BcaExecucaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tipo' => $this->faker->randomElement(['automatica', 'manual']),
            'data_execucao' => now(),
            'status' => $this->faker->randomElement(['sucesso', 'falha', 'sem_bca']),
            'mensagem' => $this->faker->optional()->text(100),
            'registros_processados' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function sucesso(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sucesso',
        ]);
    }

    public function falha(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'falha',
            'mensagem' => 'Erro de teste',
        ]);
    }
}
