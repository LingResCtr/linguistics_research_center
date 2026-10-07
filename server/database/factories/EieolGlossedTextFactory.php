<?php

namespace Database\Factories;

use App\Models\EieolGlossedText;
use App\Models\EieolLesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EieolGlossedText>
 */
class EieolGlossedTextFactory extends Factory
{
    protected $model = EieolGlossedText::class;

    public function definition(): array
    {
        return [
            'lesson_id' => EieolLesson::factory(),
            'glossed_text' => '<p>'.fake()->sentence().'</p>',
            'order' => 1,
            'audio_url' => null,
            'custom_gloss_mapping' => null,
        ];
    }
}
