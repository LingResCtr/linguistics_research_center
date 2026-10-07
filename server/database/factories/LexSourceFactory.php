<?php

namespace Database\Factories;

use App\Models\LexLexicon;
use App\Models\LexSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexSource>
 */
class LexSourceFactory extends Factory
{
    protected $model = LexSource::class;

    public function definition(): array
    {
        return [
            'lexicon_id' => LexLexicon::factory(),
            'code' => fake()->unique()->lexify('???'),
            'display' => fake()->sentence(4),
        ];
    }
}
