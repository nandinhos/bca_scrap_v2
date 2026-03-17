<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EfetivoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'saram' => $this->faker->unique()->numerify('#######'),
            'nome_guerra' => strtoupper($this->faker->lastName()),
            'nome_completo' => strtoupper($this->faker->name()),
            'posto' => $this->faker->randomElement(['Cel Av', 'Ten Cel', 'Maj', 'Cap', '1° Ten', 'SO', '1S', '2S']),
            'especialidade' => null,
            'email' => $this->faker->email(),
            'om_origem' => 'GAC-PAC',
            'ativo' => true,
            'oculto' => false,
        ];
    }
}
