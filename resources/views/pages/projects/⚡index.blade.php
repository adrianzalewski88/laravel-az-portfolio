
<?php

use App\Models\Category;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public string $category = 'all';
    public string $viewMode = 'grid';
    public string $sort = 'newest';

    public function mount(): void
    {
        $this->authorize('viewAny', Project::class);
    }

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

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['grid', 'list'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'status',
            'category',
            'sort',
        ]);

        $this->resetPage();
    }

    private function filteredQuery(): Builder
    {
        return Project::query()
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
                $this->status === 'published',
                fn (Builder $query) => $query
                    ->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
            )
            ->when(
                $this->status === 'draft',
                fn (Builder $query) => $query
                    ->where('status', 'draft')
            )
            ->when(
                $this->status === 'scheduled',
                fn (Builder $query) => $query
                    ->where('status', 'published')
                    ->where('published_at', '>', now())
            )
            ->when(
                $this->category !== 'all',
                fn (Builder $query) => $query
                    ->whereHas(
                        'categories',
                        fn (Builder $categories) => $categories
                            ->whereKey((int) $this->category)
                    )
            );
    }

    public function delete(int $projectId): void
    {
        $project = Project::findOrFail($projectId);

        $this->authorize('delete', $project);

        // The existing image is deliberately not deleted here.
        // Other content may reference the same uploaded file.
        $project->delete();

        session()->flash(
            'success',
            'Project deleted successfully.'
        );

        $this->resetPage();
    }

    public function render()
    {
        $now = now();

        $totalProjects = Project::count();

        $publishedProjects = Project::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $now)
            ->count();

        $draftProjects = Project::query()
            ->where('status', 'draft')
            ->count();

        $scheduledProjects = Project::query()
            ->where('status', 'published')
            ->where('published_at', '>', $now)
            ->count();

        $projectsWithImages = Project::query()
            ->whereNotNull('featured_image')
            ->where('featured_image', '!=', '')
            ->count();

        $publishingRate = $totalProjects > 0
            ? round(
                ($publishedProjects / $totalProjects) * 100
            )
            : 0;

        $imageCoverage = $totalProjects > 0
            ? round(
                ($projectsWithImages / $totalProjects) * 100
            )
            : 0;

        $topCategories = Category::query()
            ->withCount('projects')
            ->has('projects')
            ->orderByDesc('projects_count')
            ->orderBy('name')
            ->limit(5)
            ->get();

        $categoryColors = [
            '#568aff',
            '#a17eff',
            '#49d4ad',
            '#f2b76a',
            '#e978ad',
        ];

        $categoryTotal = $topCategories
            ->sum('projects_count');

        $chartStops = [];
        $position = 0;

        foreach ($topCategories as $index => $category) {
            $percentage = $categoryTotal > 0
                ? (
                    $category->projects_count
                    / $categoryTotal
                ) * 100
                : 0;

            $end = $position + $percentage;

            $chartStops[] = sprintf(
                '%s %.4f%% %.4f%%',
                $categoryColors[$index],
                $position,
                $end
            );

            $position = $end;
        }

        $categoryGradient = count($chartStops)
            ? 'conic-gradient('
                . implode(', ', $chartStops)
                . ')'
            : 'conic-gradient(#293248 0% 100%)';

        $projectsQuery = $this->filteredQuery()
            ->with(['categories', 'user']);

        match ($this->sort) {
            'oldest' => $projectsQuery
                ->orderBy('created_at')
                ->orderBy('id'),

            'title' => $projectsQuery
                ->orderBy('title')
                ->orderBy('id'),

            'updated' => $projectsQuery
                ->orderByDesc('updated_at')
                ->orderByDesc('id'),

            default => $projectsQuery
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        };

        return $this->view([
            'projects' => $projectsQuery->paginate(9),

            'categories' => Category::query()
                ->orderBy('name')
                ->get(),

            'totalProjects' => $totalProjects,
            'publishedProjects' => $publishedProjects,
            'draftProjects' => $draftProjects,
            'scheduledProjects' => $scheduledProjects,
            'projectsWithImages' => $projectsWithImages,
            'publishingRate' => $publishingRate,
            'imageCoverage' => $imageCoverage,

            'topCategories' => $topCategories,
            'categoryColors' => $categoryColors,
            'categoryTotal' => $categoryTotal,
            'categoryGradient' => $categoryGradient,

            'placeholderImage' => asset(
                'images/project-placeholder.jpg'
            ),
        ]);
    }
};
?>

<div class="project-container">

    {{-- Page header --}}

    <header class="project-page-header">

        <div>
            <p class="project-eyebrow">
                Portfolio / Content Management
            </p>

            <h1 class="project-page-title">
                Projects<span class="project-title-dot">.</span>
            </h1>

            <p class="project-page-description">
                Manage your portfolio projects, track publishing
                progress and explore your most-used categories.
            </p>
        </div>

        @can('create', App\Models\Project::class)
            <a
                href="{{ route('projects.create') }}"
                class="btn-primary project-header-action"
            >
                <span>+</span>
                Add Project
            </a>
        @endcan

    </header>


    {{-- Flash message --}}

    @if (session('success'))
        <div class="project-alert project-alert--success">
            {{ session('success') }}
        </div>
    @endif


    {{-- Main statistics --}}

    <section
        class="project-stats"
        aria-label="Project statistics"
    >

        <article class="project-stat project-stat--blue">

            <div class="project-stat__top">
                <span>Total Projects</span>
                <span class="project-stat__icon">◈</span>
            </div>

            <strong class="project-stat__value">
                {{ number_format($totalProjects) }}
            </strong>

            <span class="project-stat__description">
                Portfolio records in your CMS
            </span>

        </article>


        <article class="project-stat project-stat--green">

            <div class="project-stat__top">
                <span>Published</span>
                <span class="project-stat__icon">✓</span>
            </div>

            <strong class="project-stat__value">
                {{ number_format($publishedProjects) }}
            </strong>

            <span class="project-stat__description">
                {{ $publishingRate }}% publishing rate
            </span>

        </article>


        <article class="project-stat project-stat--orange">

            <div class="project-stat__top">
                <span>Drafts</span>
                <span class="project-stat__icon">✎</span>
            </div>

            <strong class="project-stat__value">
                {{ number_format($draftProjects) }}
            </strong>

            <span class="project-stat__description">
                Projects awaiting publication
            </span>

        </article>


        <article class="project-stat project-stat--purple">

            <div class="project-stat__top">
                <span>Scheduled</span>
                <span class="project-stat__icon">◷</span>
            </div>

            <strong class="project-stat__value">
                {{ number_format($scheduledProjects) }}
            </strong>

            <span class="project-stat__description">
                Future publication dates
            </span>

        </article>

    </section>


    {{-- Publishing and category analytics --}}

    <div class="project-analytics">

        <section class="project-panel project-publishing-panel">

            <div class="project-panel__header">
                <div>
                    <p class="project-analytics-eyebrow">
                        Publishing Analytics
                    </p>

                    <h2>Publishing Progress</h2>

                    <p>
                        How much of your portfolio is live?
                    </p>
                </div>
            </div>

            <div class="project-publishing-score">

                <strong>{{ $publishingRate }}%</strong>

                <span>
                    of all projects published
                </span>

            </div>

            <div
                class="project-progress"
                role="progressbar"
                aria-label="Publishing progress"
                aria-valuenow="{{ $publishingRate }}"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <span
                    style="width: {{ $publishingRate }}%"
                ></span>
            </div>

            <div class="project-progress-details">

                <span>
                    {{ $publishedProjects }} published
                </span>

                <span>
                    {{ $totalProjects }} total
                </span>

            </div>

            <div class="project-publishing-divider"></div>

            <div class="project-coverage">

                <div class="project-coverage__heading">
                    <span>Featured Image Coverage</span>
                    <strong>{{ $imageCoverage }}%</strong>
                </div>

                <div
                    class="project-progress project-progress--purple"
                    role="progressbar"
                    aria-label="Featured image coverage"
                    aria-valuenow="{{ $imageCoverage }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <span
                        style="width: {{ $imageCoverage }}%"
                    ></span>
                </div>

                <p>
                    {{ $projectsWithImages }} of
                    {{ $totalProjects }} projects have
                    featured images.
                </p>

            </div>

        </section>


        <section class="project-panel project-categories-panel">

            <div class="project-panel__header">
                <div>
                    <p class="project-analytics-eyebrow">
                        Category Analytics
                    </p>

                    <h2>Top 5 Categories</h2>

                    <p>
                        Most frequently assigned project categories.
                    </p>
                </div>
            </div>

            @if ($categoryTotal > 0)

                <div class="project-category-chart-layout">

                    <div
                        class="project-category-doughnut"
                        style="--category-gradient: {{ $categoryGradient }};"
                        role="img"
                        aria-label="Top five category distribution"
                    >
                        <div class="project-category-doughnut__center">
                            <strong>
                                {{ $categoryTotal }}
                            </strong>

                            <span>Assignments</span>
                        </div>
                    </div>

                    <div class="project-category-legend">

                        @foreach ($topCategories as $index => $category)

                            <div class="project-category-legend__item">

                                <span
                                    class="project-category-legend__dot"
                                    style="background-color: {{ $categoryColors[$index] }}"
                                ></span>

                                <div>
                                    <strong>
                                        {{ $category->name }}
                                    </strong>

                                    <small>
                                        {{ round(
                                            $category->projects_count
                                            / $categoryTotal * 100,
                                            1
                                        ) }}%
                                    </small>
                                </div>

                                <b>
                                    {{ $category->projects_count }}
                                </b>

                            </div>

                        @endforeach

                    </div>

                </div>

            @else

                <div class="project-chart-empty">
                    <strong>No category data yet</strong>

                    <p>
                        Assign categories to your projects
                        to populate this chart.
                    </p>
                </div>

            @endif

        </section>

    </div>


    {{-- Search and filtering --}}

    <section class="project-library">

        <div class="project-library__heading">

            <div>
                <p class="project-analytics-eyebrow">
                    Project Library
                </p>

                <h2>All Projects</h2>

                <p>
                    Search, filter and manage your portfolio.
                </p>
            </div>

            <div class="project-view-toggle">

                <button
                    type="button"
                    wire:click="setViewMode('grid')"
                    class="{{ $viewMode === 'grid' ? 'is-active' : '' }}"
                    aria-label="Grid view"
                    aria-pressed="{{ $viewMode === 'grid' ? 'true' : 'false' }}"
                >
                    ▦
                </button>

                <button
                    type="button"
                    wire:click="setViewMode('list')"
                    class="{{ $viewMode === 'list' ? 'is-active' : '' }}"
                    aria-label="List view"
                    aria-pressed="{{ $viewMode === 'list' ? 'true' : 'false' }}"
                >
                    ☷
                </button>

            </div>

        </div>


        <div class="project-toolbar">

            <div class="project-search">

                <span class="project-search__icon">⌕</span>

                <input
                    type="search"
                    wire:model.live.debounce.350ms="search"
                    placeholder="Search projects..."
                    aria-label="Search projects"
                >

            </div>


            <select
                wire:model.live="status"
                class="project-filter"
                aria-label="Filter by status"
            >
                <option value="all">All Statuses</option>
                <option value="published">Published</option>
                <option value="draft">Drafts</option>
                <option value="scheduled">Scheduled</option>
            </select>


            <select
                wire:model.live="category"
                class="project-filter"
                aria-label="Filter by category"
            >
                <option value="all">All Categories</option>

                @foreach ($categories as $categoryOption)
                    <option value="{{ $categoryOption->id }}">
                        {{ $categoryOption->name }}
                    </option>
                @endforeach
            </select>


            <select
                wire:model.live="sort"
                class="project-filter"
                aria-label="Sort projects"
            >
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="updated">Recently Updated</option>
                <option value="title">Title A–Z</option>
            </select>

        </div>


        <div class="project-library__meta">

            <span>
                Showing
                <strong>{{ $projects->total() }}</strong>
                {{ Str::plural('project', $projects->total()) }}
            </span>

            @if (
                $search !== ''
                || $status !== 'all'
                || $category !== 'all'
                || $sort !== 'newest'
            )
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="project-clear-filters"
                >
                    Clear Filters
                </button>
            @endif

        </div>


        {{-- Project cards --}}

        @if ($projects->count())

            <div class="project-library-grid {{ $viewMode === 'list' ? 'project-library-grid--list' : '' }}">

                @foreach ($projects as $project)

                    @php
                        $isPublished =
                            $project->status === 'published'
                            && $project->published_at
                            && $project->published_at->isPast();

                        $isScheduled =
                            $project->status === 'published'
                            && $project->published_at
                            && $project->published_at->isFuture();

                        $displayStatus = $isPublished
                            ? 'Published'
                            : (
                                $isScheduled
                                    ? 'Scheduled'
                                    : ucfirst($project->status)
                            );

                        $imageUrl = $project->featured_image
                            ? (
                                filter_var(
                                    $project->featured_image,
                                    FILTER_VALIDATE_URL
                                )
                                    ? $project->featured_image
                                    : Storage::disk('public')->url(
                                        $project->featured_image
                                    )
                            )
                            : $placeholderImage;
                    @endphp

                    <article
                        class="project-library-card"
                        wire:key="project-{{ $project->id }}"
                    >

                        <a
                            href="{{ route('projects.edit', $project) }}"
                            class="project-library-card__image"
                        >
                            <img
                                src="{{ $imageUrl }}"
                                alt="{{ $project->title }}"
                                loading="lazy"
                                onerror="this.onerror=null;this.src='{{ $placeholderImage }}';"
                            >

                            <span
                                class="project-library-card__status
                                    {{ $isPublished ? 'is-published' : '' }}
                                    {{ $isScheduled ? 'is-scheduled' : '' }}"
                            >
                                {{ $displayStatus }}
                            </span>
                        </a>


                        <div class="project-library-card__body">

                            <div class="project-library-card__categories">

                                @forelse ($project->categories->take(3) as $projectCategory)

                                    <span>
                                        {{ $projectCategory->name }}
                                    </span>

                                @empty

                                    <span>Uncategorized</span>

                                @endforelse

                            </div>


                            <h3>
                                <a
                                    href="{{ route('projects.edit', $project) }}"
                                >
                                    {{ $project->title }}
                                </a>
                            </h3>


                            <p class="project-library-card__description">
                                {{ Str::limit(
                                    $project->short_description,
                                    130
                                ) }}
                            </p>


                            <div class="project-library-card__meta">

                                <span>
                                    Updated
                                    {{ $project->updated_at->diffForHumans() }}
                                </span>

                                @if ($project->published_at)
                                    <span>
                                        {{ $project->published_at->format('M j, Y') }}
                                    </span>
                                @endif

                            </div>


                            <div class="project-library-card__actions">

                                @can('update', $project)
                                    <a
                                        href="{{ route('projects.edit', $project) }}"
                                        class="project-library-card__edit"
                                    >
                                        Edit Project &rarr;
                                    </a>
                                @endcan

                                @can('delete', $project)
                                    <button
                                        type="button"
                                        wire:click="delete({{ $project->id }})"
                                        wire:confirm="Delete this project?"
                                        class="project-delete"
                                    >
                                        Delete
                                    </button>
                                @endcan

                            </div>

                        </div>

                    </article>

                @endforeach

            </div>


            <div class="project-library-pagination">
                {{ $projects->links() }}
            </div>

        @else

            <div class="project-library-empty">

                <div class="project-library-empty__icon">
                    ◈
                </div>

                <h3>No projects found</h3>

                <p>
                    No projects match your current filters.
                    Try another search or create a new project.
                </p>

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="project-secondary-button"
                >
                    Clear Filters
                </button>

            </div>

        @endif

    </section>

</div>