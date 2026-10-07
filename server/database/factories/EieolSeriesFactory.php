<?php

namespace Database\Factories;

use App\Models\EieolSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EieolSeries>
 */
class EieolSeriesFactory extends Factory
{
    protected $model = EieolSeries::class;

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'order' => 1,
            'menu_name' => fake()->words(2, true),
            'menu_order' => '1',
            'expanded_title' => fake()->sentence(4),
            'published' => 1,
        ];
    }
}
