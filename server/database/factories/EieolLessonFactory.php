<?php

namespace Database\Factories;

use App\Models\EieolLanguage;
use App\Models\EieolLesson;
use App\Models\EieolSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EieolLesson>
 */
class EieolLessonFactory extends Factory
{
    protected $model = EieolLesson::class;

    public function definition(): array
    {
        return [
            'series_id' => EieolSeries::factory(),
            'language_id' => EieolLanguage::factory(),
            'title' => fake()->sentence(3),
            'order' => 1,
            'intro_text' => fake()->paragraph(),
            'lesson_text' => '<p>'.fake()->paragraph().'</p>',
            'lesson_translation' => fake()->paragraph(),
        ];
    }
}
