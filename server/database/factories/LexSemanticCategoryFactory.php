<?php

namespace Database\Factories;

use App\Models\LexLexicon;
use App\Models\LexSemanticCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexSemanticCategory>
 */
class LexSemanticCategoryFactory extends Factory
{
    protected $model = LexSemanticCategory::class;

    public function definition(): array
    {
        return [
            'lexicon_id' => LexLexicon::factory(),
            'text' => [
                'en' => fake()->words(2, true),
                'es' => fake()->words(2, true),
                'te' => 'తెలుగు వర్గం',
            ],
            'number' => (string) fake()->unique()->numberBetween(1, 900),
            'abbr' => fake()->unique()->lexify('??'),
        ];
    }
}
