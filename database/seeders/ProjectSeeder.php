<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();

        if (Project::exists()) {
            return;
        }

        Project::factory()
            ->count(6)
            ->for($user)
            ->create()
            ->each(function (Project $project): void {
                $categoryIds = Category::query()
                    ->inRandomOrder()
                    ->limit(2)
                    ->pluck('id');

                $project->categories()->sync($categoryIds);
            });
    }
}