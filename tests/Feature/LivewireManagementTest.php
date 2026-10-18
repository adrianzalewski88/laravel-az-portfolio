<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class LivewireManagementTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_admin_can_create_a_project_with_categories_and_image(): void
    {
        $admin = $this->userWithPermissions('projects.create');
        $this->actingAs($admin);

        Storage::fake('public');

        $category = Category::factory()->create();

        Livewire::test('pages::projects.create')
            ->set('title', 'A Testing Project')
            ->set('short_description', 'A short project summary.')
            ->set('description', 'The full project description.')
            ->set('status', 'draft')
            ->set('selectedCategories', [$category->id])
            ->set('featured_image', UploadedFile::fake()->image('cover.jpg'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('projects.index'));

        $project = Project::query()
            ->where('slug', 'a-testing-project')
            ->firstOrFail();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'user_id' => $admin->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('category_project', [
            'project_id' => $project->id,
            'category_id' => $category->id,
        ]);

        Storage::disk('public')->assertExists($project->featured_image);
    }

    public function test_published_project_requires_a_publication_date(): void
    {
        $this->actingAs(
            $this->userWithPermissions('projects.create')
        );

        Livewire::test('pages::projects.create')
            ->set('title', 'Scheduled Project')
            ->set('short_description', 'A short project summary.')
            ->set('description', 'The full project description.')
            ->set('status', 'published')
            ->call('save')
            ->assertHasErrors(['published_at']);

        $this->assertDatabaseMissing('projects', [
            'slug' => 'scheduled-project',
        ]);
    }

    public function test_admin_can_update_project_and_sync_categories(): void
    {
        $this->actingAs(
            $this->userWithPermissions('projects.edit')
        );

        $oldCategory = Category::factory()->create();
        $newCategory = Category::factory()->create();

        $project = Project::factory()->create([
            'title' => 'Original Project',
            'slug' => 'original-project',
        ]);

        $project->categories()->attach($oldCategory);

        Livewire::test('pages::projects.edit', ['project' => $project])
            ->set('title', 'Renamed Project')
            ->set('short_description', 'Updated summary.')
            ->set('description', 'Updated description.')
            ->set('selectedCategories', [$newCategory->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('projects.edit', $project));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Renamed Project',
            'slug' => 'renamed-project',
        ]);

        $this->assertDatabaseMissing('category_project', [
            'project_id' => $project->id,
            'category_id' => $oldCategory->id,
        ]);

        $this->assertDatabaseHas('category_project', [
            'project_id' => $project->id,
            'category_id' => $newCategory->id,
        ]);
    }

    public function test_admin_can_create_category_and_slug_is_generated(): void
    {
        $this->actingAs(
            $this->userWithPermissions('projects.edit')
        );

        Livewire::test('pages::categories.create')
            ->set('name', 'PHP Testing')
            ->set('description', 'Testing examples for PHP.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'PHP Testing',
            'slug' => 'php-testing',
        ]);
    }

    public function test_deleting_category_keeps_projects_and_removes_pivot_rows(): void
    {
        $this->actingAs(
            $this->userWithPermissions(
                'projects.edit',
                'projects.delete'
            )
        );

        $category = Category::factory()->create();
        $project = Project::factory()->create();

        $project->categories()->attach($category);

        Livewire::test('pages::categories.edit', ['category' => $category])
            ->call('delete')
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);

        $this->assertDatabaseMissing('category_project', [
            'project_id' => $project->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_admin_can_create_user_and_assign_admin_role(): void
    {
        $this->actingAs(
            $this->userWithPermissions('users.create')
        );

        Role::findOrCreate('admin', 'web');

        Livewire::test('pages::users.create')
            ->set('name', 'New Administrator')
            ->set('email', 'new-admin@example.test')
            ->set('password', 'StrongPassword123!')
            ->set('password_confirmation', 'StrongPassword123!')
            ->set('role', 'admin')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $createdUser = User::query()
            ->where('email', 'new-admin@example.test')
            ->firstOrFail();

        $this->assertTrue(
            Hash::check('StrongPassword123!', $createdUser->password)
        );

        $this->assertTrue($createdUser->hasRole('admin'));
    }

    public function test_user_cannot_delete_their_own_account(): void
    {
        $user = $this->userWithPermissions(
            'users.edit',
            'users.delete'
        );

        $this->actingAs($user);

        Livewire::test('pages::users.edit', ['user' => $user])
            ->call('delete');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard/projects')
            ->assertRedirect('/login');
    }
}
