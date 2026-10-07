<?php

namespace Database\Factories;

use App\Models\LexLanguage;
use App\Models\LexLanguageSubFamily;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexLanguage>
 */
class LexLanguageFactory extends Factory
{
    protected $model = LexLanguage::class;

    public function definition(): array
    {
        return [
            'sub_family_id' => LexLanguageSubFamily::factory(),
            'name' => [
                'en' => fake()->unique()->word().' language',
                'es' => fake()->word().' idioma',
                'te' => 'తెలుగు భాష',
            ],
            'description' => [
                'en' => '<p>'.fake()->sentence().'</p>',
                'es' => '<p>'.fake()->sentence().'</p>',
                'te' => '<p>తెలుగు వివరణ</p>',
            ],
            'order' => 1,
            'abbr' => fake()->unique()->lexify('???'),
            'aka' => null,
            'override_family' => null,
        ];
    }
}
