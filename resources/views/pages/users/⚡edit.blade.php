<?php

use App\Models\User;
use Livewire\Component;

new class extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'admin';

    public function mount(User $user): void
    {
        $this->authorize('update', $user);

        $this->user = $user;

        $this->name = $user->name;
        $this->email = $user->email;

        if ($user->hasRole('admin')) {
            $this->role = 'admin';
        }
    }

    public function save(): void
    {
        $this->authorize('update', $this->user);

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $this->user->id,
            ],
            'password' => [
                'nullable',
                'confirmed',
                'min:12',
            ],
            'role' => [
                'required',
                'in:admin',
            ],
        ]);

        $this->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $this->user->update([
                'password' => $validated['password'],
            ]);
        }

        $this->user->syncRoles([
            $validated['role'],
        ]);

        session()->flash(
            'success',
            'User updated successfully.'
        );
    }

    public function delete(): void
    {
        if ($this->user->id === auth()->id()) {
            session()->flash(
                'error',
                'You cannot delete your own account.'
            );

            return;
        }

        $this->authorize('delete', $this->user);

        $this->user->delete();

        session()->flash(
            'success',
            'User deleted successfully.'
        );

        $this->redirect(
            route('users.index'),
            navigate: true
        );
    }

    public function render()
    {
        return $this->view();
    }
};
?>

<div class="user-container">

    <header class="user-page-header">

        <div>

            <p class="user-eyebrow">
                Access Management
            </p>

            <h1 class="user-page-title">
                Edit User
            </h1>

            <p class="user-page-description">
                Manage account details and CMS access.
            </p>

        </div>

    </header>


    @if (session('success'))

        <div class="project-alert project-alert--success">
            {{ session('success') }}
        </div>

    @endif


    @if (session('error'))

        <div class="user-alert user-alert--error">
            {{ session('error') }}
        </div>

    @endif


    <form
        wire:submit="save"
        class="project-form"
    >

        <div class="project-form__main">

            <section class="project-panel">

                <div class="project-panel__header">

                    <div>

                        <h2>
                            Account Details
                        </h2>

                        <p>
                            Update the user's account information.
                        </p>

                    </div>

                </div>


                <div class="project-field">

                    <label for="name">
                        Name
                    </label>

                    <input
                        id="name"
                        type="text"
                        wire:model="name"
                        class="project-input"
                    >

                    @error('name')
                        <span class="project-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                <div class="project-field">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        id="email"
                        type="email"
                        wire:model="email"
                        class="project-input"
                    >

                    @error('email')
                        <span class="project-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                <div class="project-field">

                    <label for="password">
                        New Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        wire:model="password"
                        class="project-input"
                        placeholder="Leave blank to keep current password"
                    >

                    <p class="project-help">
                        Leave blank if the password should not change.
                    </p>

                    @error('password')
                        <span class="project-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                <div class="project-field">

                    <label for="password_confirmation">
                        Confirm New Password
                    </label>

                    <input
                        id="password_confirmation"
                        type="password"
                        wire:model="password_confirmation"
                        class="project-input"
                    >

                </div>

            </section>

        </div>


        <aside class="project-form__sidebar">

            <section class="project-panel">

                <div class="project-panel__header">

                    <div>

                        <h2>
                            Access
                        </h2>

                        <p>
                            Configure CMS permissions.
                        </p>

                    </div>

                </div>


                <div class="project-field">

                    <label for="role">
                        Role
                    </label>

                    <select
                        id="role"
                        wire:model="role"
                        class="project-input"
                    >
                        <option value="admin">
                            Administrator
                        </option>
                    </select>

                </div>


                <div class="project-actions">

                    <button
                        type="submit"
                        class="btn-primary project-submit"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>
                            Save Changes
                        </span>

                        <span wire:loading>
                            Saving...
                        </span>
                    </button>

                    <a
                        href="{{ route('users.index') }}"
                        class="project-secondary-button"
                    >
                        Cancel
                    </a>

                    @if ($user->id !== auth()->id())

                        <button
                            type="button"
                            wire:click="delete"
                            wire:confirm="Delete this user?"
                            class="project-delete"
                        >
                            Delete User
                        </button>

                    @endif

                </div>

            </section>


            <section class="project-panel">

                <div class="project-panel__header">

                    <div>

                        <h2>
                            Account
                        </h2>

                    </div>

                </div>

                <div class="project-meta">

                    <div class="project-meta__item">
                        <span>
                            Projects
                        </span>

                        <span>
                            {{ $user->projects()->count() }}
                        </span>
                    </div>

                    <div class="project-meta__item">
                        <span>
                            Created
                        </span>

                        <span>
                            {{ $user->created_at->format('M j, Y') }}
                        </span>
                    </div>

                    <div class="project-meta__item">
                        <span>
                            Last Updated
                        </span>

                        <span>
                            {{ $user->updated_at->format('M j, Y') }}
                        </span>
                    </div>

                </div>

            </section>

        </aside>

    </form>

</div>