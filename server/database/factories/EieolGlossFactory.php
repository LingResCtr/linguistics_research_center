<?php

namespace Database\Factories;

use App\Models\EieolGloss;
use App\Models\EieolGlossedText;
use App\Models\EieolLanguage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EieolGloss>
 */
class EieolGlossFactory extends Factory
{
    protected $model = EieolGloss::class;

    public function definition(): array
    {
        return [
            'glossed_text_id' => EieolGlossedText::factory(),
            'language_id' => EieolLanguage::factory(),
            'surface_form' => fake()->word(),
            'contextual_gloss' => fake()->word(),
            'comments' => fake()->sentence(),
            'underlying_form' => fake()->word(),
            'order' => 1,
        ];
    }
}
