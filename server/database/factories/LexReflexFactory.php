<?php

namespace Database\Factories;

use App\Models\LexLanguage;
use App\Models\LexReflex;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexReflex>
 */
class LexReflexFactory extends Factory
{
    protected $model = LexReflex::class;

    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'language_id' => LexLanguage::factory(),
            'lang_attribute' => 'en',
            'gloss' => [
                'en' => fake()->word(),
                'es' => fake()->word(),
                'te' => 'తెలుగు అర్థం',
            ],
            'entries' => [
                ['text' => $word],
            ],
        ];
    }
}
