<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookSection>
 */
class BookSectionFactory extends Factory
{
    protected $model = BookSection::class;

    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'name' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'order' => 1,
            'content' => '<p>'.fake()->paragraph().'</p>',
        ];
    }
}
