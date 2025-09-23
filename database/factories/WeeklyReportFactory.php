<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WeeklyReport>
 */
class WeeklyReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = fake()->dateTimeBetween('-2 months', 'now');
        $periodEnd = (clone $periodStart)->modify('+6 days');

        return [
            'employee_id' => 'EMP'.fake()->numberBetween(1000, 9999),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => fake()->randomElement(['draft', 'submitted', 'archived']),
            'submitted_at' => fake()->optional(0.7)->dateTimeBetween($periodStart, 'now'),
        ];
    }
}
