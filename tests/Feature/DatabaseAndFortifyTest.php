<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class DatabaseAndFortifyTest extends TestCase
{
    use InteractsWithPermissions;
    use RefreshDatabase;

    public function test_model_relationships_and_database_cascades_work(): void
    {
        $project = Project::factory()->create();
        $category = Category::factory()->create();

        $project->categories()->attach($category);

        $this->assertTrue($project->user->is($project->user));
        $this->assertTrue($project->categories->contains($category));
        $this->assertInstanceOf(
            Carbon::class,
            $project->fresh()->published_at
        );

        $projectId = $project->id;
        $user = $project->user;

        $user->delete();

        $this->assertDatabaseMissing('projects', [
            'id' => $projectId,
        ]);

        $this->assertDatabaseMissing('category_project', [
            'project_id' => $projectId,
            'category_id' => $category->id,
        ]);
    }

    public function test_database_rejects_duplicate_project_slugs(): void
    {
        Project::factory()->create([
            'slug' => 'duplicate-project-slug',
        ]);

        $this->expectException(QueryException::class);

        Project::factory()->create([
            'slug' => 'duplicate-project-slug',
        ]);
    }

    public function test_role_permission_seeder_is_repeatable(): void
    {
        $seeder = app(RolePermissionSeeder::class);

        $seeder->run();
        $seeder->run();

        $admin = Role::findByName('admin', 'web');

        $this->assertSame(7, $admin->permissions()->count());

        foreach ([
            'projects.create',
            'projects.edit',
            'projects.delete',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
        ] as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_project_policy_allows_public_view_only_when_published(): void
    {
        $visitor = User::factory()->create();
        $editor = $this->userWithPermissions('projects.edit');

        $published = Project::factory()->create([
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        $draft = Project::factory()->create([
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->assertTrue(
            Gate::forUser($visitor)->allows('view', $published)
        );

        $this->assertFalse(
            Gate::forUser($visitor)->allows('view', $draft)
        );

        $this->assertTrue(
            Gate::forUser($editor)->allows('view', $draft)
        );
    }

    public function test_fortify_registration_action_hashes_password(): void
    {
        $user = app(CreateNewUser::class)->create([
            'name' => 'Registered User',
            'email' => 'registered@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'registered@example.test',
        ]);

        $this->assertTrue(
            Hash::check('StrongPassword123!', $user->password)
        );
    }

    public function test_fortify_registration_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.test',
        ]);

        $this->expectException(ValidationException::class);

        app(CreateNewUser::class)->create([
            'name' => 'Duplicate User',
            'email' => 'existing@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ]);
    }

    public function test_fortify_profile_update_allows_unchanged_email(): void
    {
        $user = User::factory()->create();

        app(UpdateUserProfileInformation::class)->update($user, [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);
    }

    public function test_fortify_password_update_requires_current_password_and_hashes_new_one(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        app(UpdateUserPassword::class)->update($user, [
            'current_password' => 'password',
            'password' => 'NewStrongPassword123!',
            'password_confirmation' => 'NewStrongPassword123!',
        ]);

        $this->assertTrue(
            Hash::check(
                'NewStrongPassword123!',
                $user->fresh()->password
            )
        );
    }

    public function test_fortify_password_reset_action_hashes_the_new_password(): void
    {
        $user = User::factory()->create();

        app(ResetUserPassword::class)->reset($user, [
            'password' => 'ResetStrongPassword123!',
            'password_confirmation' => 'ResetStrongPassword123!',
        ]);

        $this->assertTrue(
            Hash::check(
                'ResetStrongPassword123!',
                $user->fresh()->password
            )
        );
    }
}
