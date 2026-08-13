<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state: a draft.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(4), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => fake()->paragraphs(3, true),
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the page is publicly visible.
     */
    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'published_at' => now()->subDay(),
        ]);
    }
}
