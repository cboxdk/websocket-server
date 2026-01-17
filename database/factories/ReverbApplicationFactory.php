<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReverbApplication>
 */
class ReverbApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'key' => Str::random(20),
            'secret' => Str::random(40),
            'name' => fake()->company(),
            'allowed_origins' => ['*'],
            'enable_client_messages' => false,
            'max_connections' => null,
            'max_message_size' => 10000,
            'options' => [
                'host' => 'localhost',
                'port' => 8080,
                'scheme' => 'http',
                'useTLS' => false,
                'ping_interval' => 60,
                'activity_timeout' => 30,
            ],
        ];
    }

    public function withClientMessages(): static
    {
        return $this->state(fn (array $attributes) => [
            'enable_client_messages' => true,
        ]);
    }

    public function withMaxConnections(int $max): static
    {
        return $this->state(fn (array $attributes) => [
            'max_connections' => $max,
        ]);
    }

    public function withAllowedOrigins(array $origins): static
    {
        return $this->state(fn (array $attributes) => [
            'allowed_origins' => $origins,
        ]);
    }
}
