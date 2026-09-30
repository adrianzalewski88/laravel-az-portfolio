<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'user_id' => User::factory(),

            'title' => $title,

            'slug' => Str::slug($title),

            'short_description' => fake()->sentence(15),

            'description' => fake()->paragraphs(3, true),

            'featured_image' => 'https://picsum.photos/1200/800',

            'status' => 'published',

            'published_at' => now(),
        ];
    }
}