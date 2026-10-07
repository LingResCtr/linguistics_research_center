<?php

namespace Database\Factories;

use App\Models\EieolHeadWord;
use App\Models\EieolLanguage;
use App\Models\LexEtyma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EieolHeadWord>
 */
class EieolHeadWordFactory extends Factory
{
    protected $model = EieolHeadWord::class;

    public function definition(): array
    {
        return [
            'word' => fake()->word(),
            'definition' => fake()->word(),
            'language_id' => EieolLanguage::factory(),
            'etyma_id' => LexEtyma::factory(),
            'keywords' => fake()->word().','.fake()->word(),
        ];
    }
}
