<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicPortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_displays_only_projects_published_by_now(): void
    {
        $visible = Project::factory()->create([
            'title' => 'Visible Portfolio Project',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Project::factory()->create([
            'title' => 'Draft Project Secret',
            'status' => 'draft',
            'published_at' => null,
        ]);

        Project::factory()->create([
            'title' => 'Future Project Secret',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        Livewire::test('pages::home')
            ->assertSee($visible->title)
            ->assertDontSee('Draft Project Secret')
            ->assertDontSee('Future Project Secret');
    }

    public function test_public_projects_api_filters_and_orders_projects(): void
    {
        $older = Project::factory()->create([
            'slug' => 'older-visible-project',
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'featured_image' => 'https://example.test/older.jpg',
        ]);

        $category = Category::factory()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $older->categories()->attach($category);

        Project::factory()->create([
            'slug' => 'hidden-draft-project',
            'status' => 'draft',
            'published_at' => null,
        ]);

        Project::factory()->create([
            'slug' => 'hidden-future-project',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->getJson('/api/v1/public/projects')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'older-visible-project')
            ->assertJsonPath('data.0.categories.0.slug', 'laravel')
            ->assertJsonPath(
                'data.0.featuredImage',
                'https://example.test/older.jpg'
            );
    }

    public function test_public_project_detail_returns_404_for_unpublished_projects(): void
    {
        $published = Project::factory()->create([
            'slug' => 'published-project',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $draft = Project::factory()->create([
            'slug' => 'draft-project',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $future = Project::factory()->create([
            'slug' => 'future-project',
            'status' => 'published',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('projects.show', $published->slug))
            ->assertOk();

        $this->get(route('projects.show', $draft->slug))
            ->assertNotFound();

        $this->get(route('projects.show', $future->slug))
            ->assertNotFound();

        $this->getJson('/api/v1/public/projects/missing-project')
            ->assertNotFound();
    }

    public function test_public_categories_api_returns_categories_in_name_order(): void
    {
        Category::factory()->create([
            'name' => 'Zig',
            'slug' => 'zig',
        ]);

        Category::factory()->create([
            'name' => 'Alpha',
            'slug' => 'alpha',
        ]);

        $this->getJson('/api/v1/public/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Alpha')
            ->assertJsonPath('data.1.name', 'Zig');

        $this->getJson('/api/v1/public/categories/missing-category')
            ->assertNotFound();
    }

    public function test_homepage_search_and_category_filters_work(): void
    {
        $laravel = Category::factory()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $react = Category::factory()->create([
            'name' => 'React',
            'slug' => 'react',
        ]);

        $laravelProject = Project::factory()->create([
            'title' => 'Laravel Portfolio',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $reactProject = Project::factory()->create([
            'title' => 'React Portfolio',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $laravelProject->categories()->attach($laravel);
        $reactProject->categories()->attach($react);

        Livewire::test('pages::home')
            ->set('search', 'Laravel')
            ->assertSee('Laravel Portfolio')
            ->assertDontSee('React Portfolio')
            ->set('search', '')
            ->set('category', 'laravel')
            ->assertSee('Laravel Portfolio')
            ->assertDontSee('React Portfolio');
    }

    public function test_homepage_rejects_an_unknown_view_mode(): void
    {
        Livewire::test('pages::home')
            ->call('setViewMode', 'list')
            ->assertSet('viewMode', 'list')
            ->call('setViewMode', 'not-a-real-view')
            ->assertSet('viewMode', 'list');
    }
}
