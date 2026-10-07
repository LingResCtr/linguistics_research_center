<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug();

        return [
            'slug' => $slug,
            'name' => [
                'en' => fake()->sentence(3),
                'es' => fake()->sentence(3),
                'te' => 'తెలుగు పేజీ',
            ],
            'content' => [
                'en' => '<p>'.fake()->paragraph().'</p>',
                'es' => '<p>'.fake()->paragraph().'</p>',
                'te' => '<p>తెలుగు వచనం</p>',
            ],
        ];
    }
}
