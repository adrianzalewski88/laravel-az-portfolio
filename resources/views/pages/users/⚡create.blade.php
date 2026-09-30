<?php

use App\Models\User;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'admin';

    public function save(): void
    {
        $this->authorize('create', User::class);

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
                'unique:users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                'min:10',
            ],
            'role' => [
                'required',
                'in:admin',
            ],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->assignRole($validated['role']);

        session()->flash(
            'success',
            'User created successfully.'
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
                Create User
            </h1>

            <p class="user-page-description">
                Add a trusted account with access to manage
                your portfolio content.
            </p>

        </div>

    </header>


    <form
        wire:submit="save"
        class="project-form"
    >

        <div class="project-form__main">

            <section class="project-panel">

                <div class="project-panel__header">

                    <div>

                        <h2>
                            Account Information
                        </h2>

                        <p>
                            Create the login credentials for this account.
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
                        placeholder="Adrian Zalewski"
                        autocomplete="name"
                    >

                    @error('name')
                        <p class="project-error">
                            {{ $message }}
                        </p>
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
                        placeholder="admin@example.com"
                        autocomplete="email"
                    >

                    @error('email')
                        <p class="project-error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div class="project-field">

                    <label for="password">
                        Password
                    </label>

                    <input
                        id="password"
                        type="password"
                        wire:model="password"
                        class="project-input"
                        placeholder="Minimum 10 characters"
                        autocomplete="new-password"
                    >

                    <p class="project-help">
                        Use a strong password with at least 10 characters.
                    </p>

                    @error('password')
                        <p class="project-error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div class="project-field">

                    <label for="password_confirmation">
                        Confirm Password
                    </label>

                    <input
                        id="password_confirmation"
                        type="password"
                        wire:model="password_confirmation"
                        class="project-input"
                        placeholder="Re-enter password"
                        autocomplete="new-password"
                    >

                    @error('password_confirmation')
                        <p class="project-error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </section>

        </div>


        <aside class="project-form__sidebar">

            <section class="project-panel user-access-panel">

                <div class="user-access-panel__badge">
                    ◆
                </div>

                <div class="project-panel__header">

                    <div>

                        <h2>
                            Access Level
                        </h2>

                        <p>
                            Configure what this account can access.
                        </p>

                    </div>

                </div>


                <div class="user-access-role">

                    <div class="user-access-role__indicator"></div>

                    <div>

                        <strong>
                            Administrator
                        </strong>

                        <span>
                            Full CMS access
                        </span>

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

                    @error('role')
                        <p class="project-error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div class="user-security-note">

                    <span>
                        🔒
                    </span>

                    <p>
                        Administrator accounts can manage users,
                        projects, categories, and other CMS content.
                    </p>

                </div>


                <div class="project-actions">

                    <button
                        type="submit"
                        class="btn-primary project-submit"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>
                            Create User
                        </span>

                        <span wire:loading>
                            Creating...
                        </span>
                    </button>

                    <a
                        href="{{ route('users.index') }}"
                        class="project-secondary-button"
                    >
                        Cancel
                    </a>

                </div>

            </section>

        </aside>

    </form>

</div>