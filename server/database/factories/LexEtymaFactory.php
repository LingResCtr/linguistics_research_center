<?php

namespace Database\Factories;

use App\Models\LexEtyma;
use App\Models\LexLexicon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexEtyma>
 */
class LexEtymaFactory extends Factory
{
    protected $model = LexEtyma::class;

    public function definition(): array
    {
        return [
            'lexicon_id' => LexLexicon::factory(),
            'entry' => '*'.fake()->unique()->word(),
            'order' => 1,
            'old_id' => null,
            'page_number' => (string) fake()->numberBetween(1, 900),
            'homograph_number' => null,
            'gloss' => [
                'en' => fake()->word(),
                'es' => fake()->word(),
                'te' => 'తెలుగు అర్థం',
            ],
        ];
    }
}
