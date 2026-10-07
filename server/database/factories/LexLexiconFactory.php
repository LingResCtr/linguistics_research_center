<?php

namespace Database\Factories;

use App\Models\LexLexicon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexLexicon>
 */
class LexLexiconFactory extends Factory
{
    protected $model = LexLexicon::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'protolang_name' => [
                'en' => 'Proto-'.fake()->word(),
                'es' => 'Proto-'.fake()->word(),
                'te' => 'ప్రోటో-భాష',
            ],
            'viewer_lang_options' => 'en, es, te',
            'landing_page_content' => [
                'en' => '<p>'.fake()->paragraph().'</p>',
                'es' => '<p>'.fake()->paragraph().'</p>',
                'te' => '<p>తెలుగు వచనం</p>',
            ],
            'protolanguage_page_content' => [
                'en' => '<p>'.fake()->paragraph().'</p>',
                'es' => '<p>'.fake()->paragraph().'</p>',
                'te' => '<p>తెలుగు వచనం</p>',
            ],
        ];
    }
}
