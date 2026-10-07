<?php

namespace Database\Factories;

use App\Models\LexLanguageFamily;
use App\Models\LexLanguageSubFamily;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexLanguageSubFamily>
 */
class LexLanguageSubFamilyFactory extends Factory
{
    protected $model = LexLanguageSubFamily::class;

    public function definition(): array
    {
        return [
            'family_id' => LexLanguageFamily::factory(),
            'name' => [
                'en' => fake()->words(2, true).' branch',
                'es' => fake()->words(2, true).' rama',
                'te' => 'తెలుగు శాఖ',
            ],
            'order' => 1,
        ];
    }
}
