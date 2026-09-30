<?php

use App\Models\Category;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

new #[Layout('layouts.public')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = 'all';

    #[Url]
    public string $sort = 'newest';

    public string $viewMode = 'grid';

    public int $perPage = 12;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['grid', 'compact', 'list'], true)) {
            return;
        }

        $this->viewMode = $mode;
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'category',
            'sort',
        ]);

        $this->resetPage();
    }

    public function render()
    {
        $projectsQuery = Project::query()
            ->with('categories')
            ->withCount('categories')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when(
                $this->search !== '',
                fn (Builder $query) => $query->where(
                    function (Builder $query) {
                        $query
                            ->where(
                                'title',
                                'like',
                                '%' . $this->search . '%'
                            )
                            ->orWhere(
                                'short_description',
                                'like',
                                '%' . $this->search . '%'
                            );
                    }
                )
            )
            ->when(
                $this->category !== 'all',
                fn (Builder $query) => $query->whereHas(
                    'categories',
                    fn (Builder $categoryQuery) =>
                        $categoryQuery->where(
                            'categories.slug',
                            $this->category
                        )
                )
            );

        match ($this->sort) {

            'oldest' =>
                $projectsQuery
                    ->orderBy('published_at')
                    ->orderBy('id'),

            'title_asc' =>
                $projectsQuery
                    ->orderBy('title')
                    ->orderBy('id'),

            'title_desc' =>
                $projectsQuery
                    ->orderByDesc('title')
                    ->orderByDesc('id'),

            'updated' =>
                $projectsQuery
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id'),

            'categories' =>
                $projectsQuery
                    ->orderByDesc('categories_count')
                    ->orderByDesc('published_at'),

            default =>
                $projectsQuery
                    ->orderByDesc('published_at')
                    ->orderByDesc('id'),
        };

        $projects = $projectsQuery->paginate($this->perPage);

        $categories = Category::query()
            ->withCount([
                'projects' => fn (Builder $query) =>
                    $query
                        ->where('status', 'published')
                        ->whereNotNull('published_at')
                        ->where(
                            'published_at',
                            '<=',
                            now()
                        ),
            ])
            ->having('projects_count', '>', 0)
            ->orderByDesc('projects_count')
            ->orderBy('name')
            ->get();

        $publishedCount = Project::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->count();

        $categoryCount = $categories->count();

        return $this->view([
            'projects' => $projects,
            'categories' => $categories,
            'publishedCount' => $publishedCount,
            'categoryCount' => $categoryCount,
        ]);
    }
};
?>

<div class="public-home">

    {{-- HERO --}}

    <section
        class="public-hero"
        id="top"
    >

        <div class="public-hero__image"></div>

        <div class="public-hero__overlay"></div>

        <div class="public-hero__grid"></div>

        <div class="public-hero__orb public-hero__orb--one"></div>
        <div class="public-hero__orb public-hero__orb--two"></div>

        <div class="public-container public-hero__content">

            <div class="public-hero__eyebrow">
                <span class="public-hero__status"></span>

                Full Stack Development

            </div>

            <h1 class="public-hero__title">

                Laravel

                <span>
                    - AZ Portfolio
                </span>

            </h1>

            <p class="public-hero__description">

                A living portfolio of web applications,
                APIs, frameworks, infrastructure and
                experiments built with modern PHP and
                full-stack technologies.

            </p>

            <div class="public-hero__actions">

                <a
                    href="#project-gallery"
                    class="public-hero__cta"
                >

                    <span>
                        Browse Projects
                    </span>

                    <span class="public-hero__cta-arrow">
                        ↓
                    </span>

                </a>

                <a
                    href="https://github.com/adrianzalewski88"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="public-hero__secondary"
                >
                    GitHub
                </a>

            </div>

            <div class="public-hero__stats">

                <div>
                    <strong>
                        {{ $publishedCount }}
                    </strong>

                    <span>
                        Published Projects
                    </span>
                </div>

                <div>
                    <strong>
                        {{ $categoryCount }}
                    </strong>

                    <span>
                        Categories
                    </span>
                </div>

                <div>
                    <strong>
                        PHP
                    </strong>

                    <span>
                        Core Technology
                    </span>
                </div>

            </div>

        </div>

        <div class="public-hero__scroll">

            <span>
                Scroll to explore
            </span>

            <i></i>

        </div>

    </section>


    {{-- PROJECT GALLERY --}}

    <section
        class="project-gallery"
        id="project-gallery"
    >

        <div class="public-container">

            <div class="project-gallery__heading">

                <div>

                    <span class="public-section-eyebrow">
                        Portfolio Archive
                    </span>

                    <h2>
                        Project
                        <span class="gradient-text">
                            Gallery
                        </span>
                    </h2>

                    <p>
                        Explore applications, platforms,
                        APIs and experiments by technology,
                        category and chronology.
                    </p>

                </div>

                <div class="project-gallery__counter">

                    <strong>
                        {{ $projects->total() }}
                    </strong>

                    <span>
                        matching projects
                    </span>

                </div>

            </div>


            {{-- CATEGORY NAVIGATION --}}

            <div class="project-categories">

                <button
                    type="button"
                    wire:click="clearFilters"
                    class:project-category--active="$category === 'all'"
                    class="project-category"
                >
                    <span>
                        All
                    </span>

                    <small>
                        {{ $publishedCount }}
                    </small>
                </button>

                @foreach ($categories as $projectCategory)

                    <button
                        type="button"
                        wire:click="$set('category', '{{ $projectCategory->slug }}')"
                        class="project-category
                            {{ $category === $projectCategory->slug
                                ? 'project-category--active'
                                : '' }}"
                    >

                        <span>
                            {{ $projectCategory->name }}
                        </span>

                        <small>
                            {{ $projectCategory->projects_count }}
                        </small>

                    </button>

                @endforeach

            </div>


            {{-- TOOLBAR --}}

            <div class="project-gallery__toolbar">

                <div class="project-search">

                    <span>
                        ⌕
                    </span>

                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search projects..."
                    >

                </div>


                <div class="project-sort">

                    <label for="project-sort">
                        Sort
                    </label>

                    <select
                        id="project-sort"
                        wire:model.live="sort"
                    >

                        <option value="newest">
                            Newest
                        </option>

                        <option value="oldest">
                            Oldest
                        </option>

                        <option value="title_asc">
                            Title A-Z
                        </option>

                        <option value="title_desc">
                            Title Z-A
                        </option>

                        <option value="updated">
                            Recently Updated
                        </option>

                        <option value="categories">
                            Most Categories
                        </option>

                    </select>

                </div>


                <div class="project-view-switcher">

                    <button
                        type="button"
                        wire:click="setViewMode('grid')"
                        class="{{ $viewMode === 'grid'
                            ? 'is-active'
                            : '' }}"
                        aria-label="Grid view"
                    >
                        ▦
                    </button>

                    <button
                        type="button"
                        wire:click="setViewMode('compact')"
                        class="{{ $viewMode === 'compact'
                            ? 'is-active'
                            : '' }}"
                        aria-label="Compact view"
                    >
                        ▦
                    </button>

                    <button
                        type="button"
                        wire:click="setViewMode('list')"
                        class="{{ $viewMode === 'list'
                            ? 'is-active'
                            : '' }}"
                        aria-label="List view"
                    >
                        ☰
                    </button>

                </div>

            </div>


            {{-- LIVEWIRE LOADING --}}

            <div
                wire:loading
                wire:target="search,category,sort,setViewMode"
                class="project-gallery__loading"
            >
                <span></span>
                Updating gallery...
            </div>


            {{-- PROJECTS --}}

            <div
                class="project-results
                    project-results--{{ $viewMode }}"
            >

                @forelse ($projects as $project)

                    @php
                        $image = $project->featured_image
                            ? \Illuminate\Support\Facades\Storage::url(
                                $project->featured_image
                            )
                            : asset(
                                'images/project-placeholder.jpg'
                            );
                    @endphp

                    <article
                        wire:key="project-{{ $project->id }}"
                        class="public-project-card"
                    >

                        <a
                            href="{{ route(
                                'projects.show',
                                $project->slug
                            ) }}"
                            class="public-project-card__link"
                        >

                            <div class="public-project-card__image">

                                <img
                                    src="{{ $image }}"
                                    alt="{{ $project->title }}"
                                    loading="lazy"
                                >

                                <div class="public-project-card__image-overlay"></div>

                                <span class="public-project-card__view">
                                    View Project
                                    →
                                </span>

                            </div>


                            <div class="public-project-card__body">

                                <div class="public-project-card__meta">

                                    <span>
                                        {{ optional($project->published_at)->format('M Y') }}
                                    </span>

                                    <span>
                                        {{ $project->categories_count }}
                                        {{ Str::plural('category', $project->categories_count) }}
                                    </span>

                                </div>

                                <h3>
                                    {{ $project->title }}
                                </h3>

                                <p>
                                    {{ $project->short_description }}
                                </p>


                                <div class="public-project-card__categories">

                                    @foreach (
                                        $project->categories->take(3)
                                        as $projectCategory
                                    )

                                        <span>
                                            {{ $projectCategory->name }}
                                        </span>

                                    @endforeach

                                </div>

                            </div>

                        </a>

                    </article>

                @empty

                    <div class="project-results__empty">

                        <div>
                            ✦
                        </div>

                        <h3>
                            No projects found
                        </h3>

                        <p>
                            Try another search, category,
                            or sort option.
                        </p>

                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="btn-primary"
                        >
                            Reset Gallery
                        </button>

                    </div>

                @endforelse

            </div>


            @if ($projects->hasPages())

                <div class="public-pagination">
                    {{ $projects->links() }}
                </div>

            @endif

        </div>

    </section>


    <x-public-footer />

</div>