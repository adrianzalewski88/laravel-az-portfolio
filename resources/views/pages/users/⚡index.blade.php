<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(User $user): void
    {
        if ($user->id === auth()->id()) {
            session()->flash(
                'error',
                'You cannot delete your own account.'
            );

            return;
        }

        $this->authorize('delete', $user);

        $user->delete();

        session()->flash(
            'success',
            'User deleted successfully.'
        );
    }

    public function render()
    {
        return $this->view([
            'users' => User::query()
                ->withCount('projects')
                ->when(
                    $this->search,
                    fn ($query) => $query
                        ->where(function ($query) {
                            $query
                                ->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                        })
                )
                ->orderBy('name')
                ->paginate(12),

            'userCount' => User::count(),

            'adminCount' => User::role('admin')->count(),

            'projectCount' => User::withCount('projects')
                ->get()
                ->sum('projects_count'),
        ]);
    }
};
?>

<div class="user-container">

    {{-- ======================================================
         Header
    ======================================================= --}}

    <header class="user-page-header">

        <div>

            <p class="user-eyebrow">
                Access Management
            </p>

            <h1 class="user-page-title">
                Users
            </h1>

            <p class="user-page-description">
                Manage the people who have access to the
                AZ Portfolio content system.
            </p>

        </div>

        <a
            href="{{ route('users.create') }}"
            class="btn-primary user-header-button"
        >
            <span>+</span>
            Add User
        </a>

    </header>


    {{-- ======================================================
         Stats
    ======================================================= --}}

    <section class="user-stats">

        <article class="user-stat">

            <div class="user-stat__icon">
                #
            </div>

            <div>
                <span class="user-stat__label">
                    Total Users
                </span>

                <strong class="user-stat__value">
                    {{ $userCount }}
                </strong>
            </div>

        </article>


        <article class="user-stat">

            <div class="user-stat__icon">
                ◆
            </div>

            <div>
                <span class="user-stat__label">
                    Administrators
                </span>

                <strong class="user-stat__value">
                    {{ $adminCount }}
                </strong>
            </div>

        </article>


        <article class="user-stat">

            <div class="user-stat__icon">
                ◈
            </div>

            <div>
                <span class="user-stat__label">
                    Projects Managed
                </span>

                <strong class="user-stat__value">
                    {{ $projectCount }}
                </strong>
            </div>

        </article>

    </section>


    {{-- ======================================================
         Users Panel
    ======================================================= --}}

    <section class="user-panel">

        <div class="user-panel__header">

            <div>

                <p class="user-panel__eyebrow">
                    Team
                </p>

                <h2>
                    CMS Users
                </h2>

                <p>
                    Accounts with access to manage portfolio content.
                </p>

            </div>

            <div class="user-search">

                <span class="user-search__icon">
                    ⌕
                </span>

                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search users..."
                    aria-label="Search users"
                >

            </div>

        </div>


        @if (session('success'))

            <div class="user-alert user-alert--success">
                {{ session('success') }}
            </div>

        @endif


        @if (session('error'))

            <div class="user-alert user-alert--error">
                {{ session('error') }}
            </div>

        @endif


        @if ($users->count())

            <div class="user-list">

                @foreach ($users as $user)

                    <article class="user-card">

                        <div class="user-card__identity">

                            <div class="user-avatar">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>

                            <div class="user-card__name">

                                <h3>
                                    {{ $user->name }}
                                </h3>

                                <p>
                                    {{ $user->email }}
                                </p>

                            </div>

                        </div>


                        <div class="user-card__role">

                            @if ($user->hasRole('admin'))

                                <span class="user-role user-role--admin">
                                    Administrator
                                </span>

                            @else

                                <span class="user-role">
                                    User
                                </span>

                            @endif

                        </div>


                        <div class="user-card__projects">

                            <strong>
                                {{ $user->projects_count }}
                            </strong>

                            <span>
                                {{ Str::plural('project', $user->projects_count) }}
                            </span>

                        </div>


                        <div class="user-card__actions">

                            <a
                                href="{{ route('users.edit', $user) }}"
                                class="user-card__edit"
                            >
                                Edit
                            </a>

                            @if ($user->id !== auth()->id())

                                <button
                                    type="button"
                                    wire:click="delete({{ $user->id }})"
                                    wire:confirm="Delete this user?"
                                    class="project-delete"
                                >
                                    Delete
                                </button>

                            @else

                                <span class="user-current">
                                    Current User
                                </span>

                            @endif

                        </div>

                    </article>

                @endforeach

            </div>


            <div class="user-pagination">
                {{ $users->links() }}
            </div>

        @else

            <div class="user-empty">

                <div class="user-empty__icon">
                    #
                </div>

                <h3>
                    No users found
                </h3>

                @if ($search)

                    <p>
                        No users match
                        <strong>"{{ $search }}"</strong>.
                    </p>

                    <button
                        type="button"
                        wire:click="$set('search', '')"
                        class="project-secondary-button"
                    >
                        Clear Search
                    </button>

                @else

                    <p>
                        Create your first CMS user to get started.
                    </p>

                    <a
                        href="{{ route('users.create') }}"
                        class="btn-primary"
                    >
                        Create First User
                    </a>

                @endif

            </div>

        @endif

    </section>

</div>