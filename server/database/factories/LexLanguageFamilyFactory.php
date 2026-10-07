<?php

namespace Database\Factories;

use App\Models\LexLanguageFamily;
use App\Models\LexLexicon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexLanguageFamily>
 */
class LexLanguageFamilyFactory extends Factory
{
    protected $model = LexLanguageFamily::class;

    public function definition(): array
    {
        return [
            'lexicon_id' => LexLexicon::factory(),
            'name' => [
                'en' => fake()->words(2, true).' family',
                'es' => fake()->words(2, true).' familia',
                'te' => 'తెలుగు కుటుంబం',
            ],
            'order' => 1,
        ];
    }
}
