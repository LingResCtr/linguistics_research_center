<?php

namespace Database\Factories;

use App\Models\LexSemanticCategory;
use App\Models\LexSemanticField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexSemanticField>
 */
class LexSemanticFieldFactory extends Factory
{
    protected $model = LexSemanticField::class;

    public function definition(): array
    {
        return [
            'semantic_category_id' => LexSemanticCategory::factory(),
            'text' => [
                'en' => fake()->words(2, true),
                'es' => fake()->words(2, true),
                'te' => 'తెలుగు క్షేత్రం',
            ],
            'number' => (string) fake()->unique()->numberBetween(1, 900),
            'abbr' => fake()->unique()->lexify('??'),
        ];
    }
}
