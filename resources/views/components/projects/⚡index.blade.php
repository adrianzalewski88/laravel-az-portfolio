<?php

use App\Models\Category;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $category = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function delete(Project $project): void
    {
        $this->authorize('delete', $project);

        $project->delete();
    }

    public function render()
    {
        $projects = Project::query()
            ->with('categories')
            ->when(
                $this->search !== '',
                fn ($query) => $query->where(function ($query) {
                    $query
                        ->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere(
                            'short_description',
                            'like',
                            '%' . $this->search . '%'
                        );
                })
            )
            ->when(
                $this->status !== '',
                fn ($query) => $query->where('status', $this->status)
            )
            ->when(
                $this->category !== '',
                fn ($query) => $query->whereHas(
                    'categories',
                    fn ($query) => $query->where('categories.id', $this->category)
                )
            )
            ->latest()
            ->paginate(10);

        return view('components.projects.⚡index', [
            'projects' => $projects,
            'categories' => Category::query()
                ->orderBy('name')
                ->get(),
        ]);
    }
};
?>

<div class="admin-container">

    <header class="admin-page-header">
        <div>
            <p class="admin-eyebrow">Content Management</p>

            <h1>Projects</h1>

            <p class="admin-page-description">
                Manage the portfolio projects available to the React application.
            </p>
        </div>

        <a href="{{ route('projects.create') }}" class="btn-primary">
            Add Project
        </a>
    </header>


    <section class="admin-panel">

        <div class="admin-panel__header">
            <div>
                <p class="admin-eyebrow">Portfolio</p>
                <h2>All Projects</h2>
            </div>
        </div>


        <div class="project-filters">

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search projects..."
                class="auth-input"
            >

            <select
                wire:model.live="status"
                class="auth-input"
            >
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>

            <select
                wire:model.live="category"
                class="auth-input"
            >
                <option value="">All categories</option>

                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

        </div>


        @if ($projects->count())

            <div class="project-list">

                @foreach ($projects as $project)

                    <article class="project-card">

                        <div class="project-card__content">

                            <div class="project-card__header">

                                <div>
                                    <h3>
                                        {{ $project->title }}
                                    </h3>

                                    <p>
                                        {{ $project->short_description }}
                                    </p>
                                </div>

                                <span class="project-status project-status--{{ $project->status }}">
                                    {{ ucfirst($project->status) }}
                                </span>

                            </div>


                            @if ($project->categories->count())

                                <div class="project-card__categories">

                                    @foreach ($project->categories as $category)

                                        <span>
                                            {{ $category->name }}
                                        </span>

                                    @endforeach

                                </div>

                            @endif


                            <div class="project-card__footer">

                                <span>
                                    {{ $project->created_at->format('M j, Y') }}
                                </span>

                                <div class="project-card__actions">

                                    <a
                                        href="{{ route('projects.edit', $project) }}"
                                        class="admin-panel__link"
                                    >
                                        Edit
                                    </a>

                                    @can('delete', $project)

                                        <button
                                            type="button"
                                            wire:click="delete({{ $project->id }})"
                                            wire:confirm="Are you sure you want to delete this project?"
                                            class="project-delete"
                                        >
                                            Delete
                                        </button>

                                    @endcan

                                </div>

                            </div>

                        </div>

                    </article>

                @endforeach

            </div>


            <div class="project-pagination">
                {{ $projects->links() }}
            </div>

        @else

            <div class="admin-empty-state">

                <h3>No projects found</h3>

                <p>
                    Try changing your search or filters, or create a new
                    portfolio project.
                </p>

            </div>

        @endif

    </section>

</div>