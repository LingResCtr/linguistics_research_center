<?php

namespace Database\Factories;

use App\Models\EieolLanguage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EieolLanguage>
 */
class EieolLanguageFactory extends Factory
{
    protected $model = EieolLanguage::class;

    public function definition(): array
    {
        return [
            'language' => fake()->unique()->word().' (తెలుగు)',
            'custom_keyboard_layout' => null,
            'substitutions' => null,
            'custom_sort' => null,
            'lang_attribute' => 'en',
        ];
    }
}
