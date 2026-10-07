<?php

namespace Database\Factories;

use App\Models\EieolGrammar;
use App\Models\EieolLesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EieolGrammar>
 */
class EieolGrammarFactory extends Factory
{
    protected $model = EieolGrammar::class;

    public function definition(): array
    {
        return [
            'lesson_id' => EieolLesson::factory(),
            'title' => fake()->sentence(3),
            'order' => 1,
            'grammar_text' => '<p>'.fake()->paragraph().'</p>',
            'section_number' => (string) fake()->unique()->numberBetween(1, 900),
        ];
    }
}
