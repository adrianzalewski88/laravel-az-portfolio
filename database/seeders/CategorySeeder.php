<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'PHP',
                'slug' => 'php',
                'description' => 'PHP and backend development projects.',
            ],
            [
                'name' => 'React',
                'slug' => 'react',
                'description' => 'React and frontend development projects.',
            ],
            [
                'name' => 'Drupal',
                'slug' => 'drupal',
                'description' => 'Drupal development and customization.',
            ],
            [
                'name' => 'WordPress',
                'slug' => 'wordpress',
                'description' => 'WordPress development and customization.',
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'Other software development projects.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}