<?php

namespace Database\Factories;

use App\Models\PageGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PageGroup>
 */
class PageGroupFactory extends Factory
{
    /**
     * Define the model's default state: a root group, hidden from the menu.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = rtrim(fake()->unique()->sentence(2), '.');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'show_in_menu' => false,
            'priority' => 0,
            'parent_id' => null,
        ];
    }
}
