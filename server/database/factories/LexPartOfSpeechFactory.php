<?php

namespace Database\Factories;

use App\Models\LexLexicon;
use App\Models\LexPartOfSpeech;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexPartOfSpeech>
 */
class LexPartOfSpeechFactory extends Factory
{
    protected $model = LexPartOfSpeech::class;

    public function definition(): array
    {
        return [
            'lexicon_id' => LexLexicon::factory(),
            'code' => fake()->unique()->lexify('??'),
            'display' => [
                'en' => fake()->word(),
                'es' => fake()->word(),
                'te' => 'తెలుగు',
            ],
        ];
    }
}
