<?php

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $usage = 'all';

    public string $sort = 'projects_desc';

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedUsage(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'usage',
            'sort',
        ]);

        $this->resetPage();
    }

    public function delete(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->authorize('delete', $category);

        if ($category->projects()->exists()) {
            session()->flash(
                'error',
                'This category cannot be deleted because it is assigned to projects.'
            );

            return;
        }

        $category->delete();

        session()->flash(
            'success',
            'Category deleted successfully.'
        );

        $this->resetPage();
    }

    public function render()
    {
        /*
         * ------------------------------------------
         * Global analytics
         * ------------------------------------------
         */

        $totalCategories = Category::count();

        $categoriesWithProjects = Category::query()
            ->has('projects')
            ->count();

        $unusedCategories = $totalCategories
            - $categoriesWithProjects;

        $totalAssignments = Category::query()
            ->withCount('projects')
            ->get()
            ->sum('projects_count');

        $averageProjectsPerCategory = $totalCategories > 0
            ? round(
                $totalAssignments / $totalCategories,
                1
            )
            : 0;

        $categoryCoverage = $totalCategories > 0
            ? round(
                ($categoriesWithProjects / $totalCategories) * 100
            )
            : 0;

        /*
         * ------------------------------------------
         * Top category
         * ------------------------------------------
         */

        $largestCategory = Category::query()
            ->withCount('projects')
            ->orderByDesc('projects_count')
            ->orderBy('name')
            ->first();

        /*
         * ------------------------------------------
         * Top five categories
         * ------------------------------------------
         */

        $topCategories = Category::query()
            ->withCount('projects')
            ->orderByDesc('projects_count')
            ->orderBy('name')
            ->limit(5)
            ->get();

        $topCategoryTotal = $topCategories
            ->sum('projects_count');

        /*
         * ------------------------------------------
         * Chart colors
         * ------------------------------------------
         */

        $categoryColors = [
            '#568aff',
            '#a17eff',
            '#49d4ad',
            '#f2b76a',
            '#e978ad',
        ];

        /*
         * ------------------------------------------
         * Top category doughnut
         * ------------------------------------------
         */

        $chartStops = [];

        $position = 0;

        foreach ($topCategories as $index => $category) {

            $percentage = $topCategoryTotal > 0
                ? (
                    $category->projects_count
                    / $topCategoryTotal
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

        /*
         * ------------------------------------------
         * Category query
         * ------------------------------------------
         */

        $categoryQuery = Category::query()
            ->withCount('projects')
            ->when(
                $this->search !== '',
                fn (Builder $query) => $query->where(
                    function (Builder $query) {
                        $query
                            ->where(
                                'name',
                                'like',
                                '%' . $this->search . '%'
                            )
                            ->orWhere(
                                'description',
                                'like',
                                '%' . $this->search . '%'
                            );
                    }
                )
            )
            ->when(
                $this->usage === 'used',
                fn (Builder $query) => $query->has('projects')
            )
            ->when(
                $this->usage === 'unused',
                fn (Builder $query) => $query
                    ->doesntHave('projects')
            );

        match ($this->sort) {

            'name_asc' => $categoryQuery
                ->orderBy('name')
                ->orderBy('id'),

            'name_desc' => $categoryQuery
                ->orderByDesc('name')
                ->orderByDesc('id'),

            'projects_asc' => $categoryQuery
                ->orderBy('projects_count')
                ->orderBy('name'),

            default => $categoryQuery
                ->orderByDesc('projects_count')
                ->orderBy('name'),
        };

        return $this->view([

            'categories' => $categoryQuery
                ->paginate(12),

            'totalCategories' => $totalCategories,

            'categoriesWithProjects' => $categoriesWithProjects,

            'unusedCategories' => $unusedCategories,

            'totalAssignments' => $totalAssignments,

            'averageProjectsPerCategory' =>
                $averageProjectsPerCategory,

            'categoryCoverage' => $categoryCoverage,

            'largestCategory' => $largestCategory,

            'topCategories' => $topCategories,

            'topCategoryTotal' => $topCategoryTotal,

            'categoryColors' => $categoryColors,

            'categoryGradient' => $categoryGradient,
        ]);
    }
};
?>

<div class="category-container">

    {{-- ==================================================
         Header
    =================================================== --}}

    <header class="category-page-header">

        <div>

            <p class="category-eyebrow">
                Portfolio / Taxonomy Analytics
            </p>

            <h1 class="category-page-title">
                Categories<span class="category-title-dot">.</span>
            </h1>

            <p class="category-page-description">
                Organize your portfolio and monitor how projects
                are distributed across your content taxonomy.
            </p>

        </div>

        @can('create', App\Models\Category::class)

            <a
                href="{{ route('categories.create') }}"
                class="btn-primary category-header-action"
            >
                <span>+</span>
                Add Category
            </a>

        @endcan

    </header>


    {{-- ==================================================
         Flash Messages
    =================================================== --}}

    @if (session('success'))

        <div class="category-alert category-alert--success">
            {{ session('success') }}
        </div>

    @endif

    @if (session('error'))

        <div class="category-alert category-alert--error">
            {{ session('error') }}
        </div>

    @endif


    {{-- ==================================================
         Primary Statistics
    =================================================== --}}

    <section
        class="category-stats"
        aria-label="Category statistics"
    >

        <article class="category-stat category-stat--blue">

            <div class="category-stat__top">

                <span>
                    Total Categories
                </span>

                <span class="category-stat__icon">
                    ◇
                </span>

            </div>

            <strong class="category-stat__value">
                {{ number_format($totalCategories) }}
            </strong>

            <span class="category-stat__description">
                Taxonomy entries in the CMS
            </span>

        </article>


        <article class="category-stat category-stat--green">

            <div class="category-stat__top">

                <span>
                    Categories In Use
                </span>

                <span class="category-stat__icon">
                    ✓
                </span>

            </div>

            <strong class="category-stat__value">
                {{ number_format($categoriesWithProjects) }}
            </strong>

            <span class="category-stat__description">
                {{ $categoryCoverage }}% category coverage
            </span>

        </article>


        <article class="category-stat category-stat--orange">

            <div class="category-stat__top">

                <span>
                    Unused Categories
                </span>

                <span class="category-stat__icon">
                    ○
                </span>

            </div>

            <strong class="category-stat__value">
                {{ number_format($unusedCategories) }}
            </strong>

            <span class="category-stat__description">
                Categories with no projects
            </span>

        </article>


        <article class="category-stat category-stat--purple">

            <div class="category-stat__top">

                <span>
                    Assignments
                </span>

                <span class="category-stat__icon">
                    ◈
                </span>

            </div>

            <strong class="category-stat__value">
                {{ number_format($totalAssignments) }}
            </strong>

            <span class="category-stat__description">
                Total project-category relationships
            </span>

        </article>

    </section>


    {{-- ==================================================
         Secondary Analytics
    =================================================== --}}

    <section class="category-mini-stats">

        <article class="category-mini-stat">

            <span>
                Average Projects / Category
            </span>

            <strong>
                {{ $averageProjectsPerCategory }}
            </strong>

            <small>
                Average assignment density
            </small>

        </article>


        <article class="category-mini-stat">

            <span>
                Category Coverage
            </span>

            <strong>
                {{ $categoryCoverage }}%
            </strong>

            <div class="category-mini-progress">

                <span
                    style="width: {{ $categoryCoverage }}%"
                ></span>

            </div>

            <small>
                Categories currently assigned to projects
            </small>

        </article>


        <article class="category-mini-stat">

            <span>
                Largest Category
            </span>

            <strong class="category-mini-stat__title">

                @if ($largestCategory)

                    {{ $largestCategory->name }}

                @else

                    None

                @endif

            </strong>

            <small>

                @if ($largestCategory)

                    {{ number_format($largestCategory->projects_count) }}
                    {{ Str::plural('project', $largestCategory->projects_count) }}

                @else

                    No project assignments yet

                @endif

            </small>

        </article>

    </section>


    {{-- ==================================================
         Analytics Panels
    =================================================== --}}

    <div class="category-analytics">


        {{-- Top five chart --}}

        <section class="category-panel">

            <div class="category-panel__header">

                <div>

                    <p class="category-panel__eyebrow">
                        Distribution
                    </p>

                    <h2>
                        Top 5 Categories
                    </h2>

                    <p>
                        Project assignments across your most-used
                        categories.
                    </p>

                </div>

            </div>


            @if ($topCategoryTotal > 0)

                <div class="category-chart-layout">

                    <div
                        class="category-doughnut"
                        style="--category-gradient: {{ $categoryGradient }};"
                        role="img"
                        aria-label="Top five category distribution"
                    >

                        <div class="category-doughnut__center">

                            <strong>
                                {{ number_format($topCategoryTotal) }}
                            </strong>

                            <span>
                                Assignments
                            </span>

                        </div>

                    </div>


                    <div class="category-chart-legend">

                        @foreach ($topCategories as $index => $category)

                            @php
                                $percentage = $topCategoryTotal > 0
                                    ? round(
                                        (
                                            $category->projects_count
                                            / $topCategoryTotal
                                        ) * 100,
                                        1
                                    )
                                    : 0;
                            @endphp

                            <div class="category-chart-legend__item">

                                <span
                                    class="category-chart-legend__dot"
                                    style="background: {{ $categoryColors[$index] }}"
                                ></span>

                                <div>

                                    <strong>
                                        {{ $category->name }}
                                    </strong>

                                    <small>
                                        {{ $percentage }}%
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

                <div class="category-chart-empty">

                    <div>
                        ◇
                    </div>

                    <h3>
                        No project assignments yet
                    </h3>

                    <p>
                        Assign categories to projects to populate
                        the distribution analytics.
                    </p>

                </div>

            @endif

        </section>


        {{-- Usage analytics --}}

        <section class="category-panel">

            <div class="category-panel__header">

                <div>

                    <p class="category-panel__eyebrow">
                        Category Health
                    </p>

                    <h2>
                        Taxonomy Utilization
                    </h2>

                    <p>
                        How effectively your categories are being used.
                    </p>

                </div>

            </div>


            <div class="category-health">

                <div class="category-health__score">

                    <strong>
                        {{ $categoryCoverage }}%
                    </strong>

                    <span>
                        categories in active use
                    </span>

                </div>


                <div class="category-health__progress">

                    <div
                        class="category-health__bar"
                        role="progressbar"
                        aria-label="Category utilization"
                        aria-valuenow="{{ $categoryCoverage }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >

                        <span
                            style="width: {{ $categoryCoverage }}%"
                        ></span>

                    </div>

                    <div class="category-health__labels">

                        <span>
                            {{ $categoriesWithProjects }}
                            used
                        </span>

                        <span>
                            {{ $unusedCategories }}
                            unused
                        </span>

                    </div>

                </div>


                <div class="category-health__breakdown">

                    <div>

                        <span class="category-health__indicator category-health__indicator--used"></span>

                        <div>
                            <strong>
                                Used Categories
                            </strong>

                            <small>
                                Categories assigned to one or more projects
                            </small>
                        </div>

                        <b>
                            {{ $categoriesWithProjects }}
                        </b>

                    </div>


                    <div>

                        <span class="category-health__indicator category-health__indicator--unused"></span>

                        <div>
                            <strong>
                                Unused Categories
                            </strong>

                            <small>
                                Categories currently without projects
                            </small>
                        </div>

                        <b>
                            {{ $unusedCategories }}
                        </b>

                    </div>

                </div>

            </div>

        </section>

    </div>


    {{-- ==================================================
         Category Library
    =================================================== --}}

    <section class="category-library">

        <div class="category-library__header">

            <div>

                <p class="category-panel__eyebrow">
                    Taxonomy Library
                </p>

                <h2>
                    All Categories
                </h2>

                <p>
                    Search, analyze and manage your project categories.
                </p>

            </div>

        </div>


        {{-- Filters --}}

        <div class="category-toolbar">

            <div class="category-search">

                <span class="category-search__icon">
                    ⌕
                </span>

                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search categories..."
                    aria-label="Search categories"
                >

            </div>


            <select
                wire:model.live="usage"
                class="category-filter"
                aria-label="Filter by category usage"
            >

                <option value="all">
                    All Categories
                </option>

                <option value="used">
                    Used Categories
                </option>

                <option value="unused">
                    Unused Categories
                </option>

            </select>


            <select
                wire:model.live="sort"
                class="category-filter"
                aria-label="Sort categories"
            >

                <option value="projects_desc">
                    Most Projects
                </option>

                <option value="projects_asc">
                    Fewest Projects
                </option>

                <option value="name_asc">
                    Name A–Z
                </option>

                <option value="name_desc">
                    Name Z–A
                </option>

            </select>

        </div>


        <div class="category-library__meta">

            <span>

                Showing
                <strong>
                    {{ $categories->total() }}
                </strong>

                {{ Str::plural('category', $categories->total()) }}

            </span>


            @if (
                $search !== ''
                || $usage !== 'all'
                || $sort !== 'projects_desc'
            )

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="category-clear-filters"
                >
                    Clear Filters
                </button>

            @endif

        </div>


        {{-- Category cards --}}

        @if ($categories->count())

            <div class="category-grid">

                @foreach ($categories as $category)

                    @php
                        $maxProjects = $largestCategory?->projects_count ?? 0;

                        $relativeUsage = $maxProjects > 0
                            ? round(
                                (
                                    $category->projects_count
                                    / $maxProjects
                                ) * 100
                            )
                            : 0;
                    @endphp

                    <article
                        class="category-card"
                        wire:key="category-{{ $category->id }}"
                    >

                        <div class="category-card__top">

                            <div class="category-card__icon">
                                {{ strtoupper(substr($category->name, 0, 1)) }}
                            </div>

                            <div class="category-card__identity">

                                <h3>
                                    {{ $category->name }}
                                </h3>

                                <span>
                                    /{{ $category->slug }}
                                </span>

                            </div>


                            @if ($category->projects_count > 0)

                                <span class="category-card__status category-card__status--used">
                                    Active
                                </span>

                            @else

                                <span class="category-card__status">
                                    Unused
                                </span>

                            @endif

                        </div>


                        @if ($category->description)

                            <p class="category-card__description">
                                {{ Str::limit(
                                    $category->description,
                                    120
                                ) }}
                            </p>

                        @else

                            <p class="category-card__description category-card__description--empty">
                                No description provided.
                            </p>

                        @endif


                        <div class="category-card__metric">

                            <div class="category-card__metric-heading">

                                <span>
                                    Project Usage
                                </span>

                                <strong>
                                    {{ $category->projects_count }}
                                </strong>

                            </div>


                            <div class="category-card__progress">

                                <span
                                    style="width: {{ $relativeUsage }}%"
                                ></span>

                            </div>


                            <small>

                                @if ($category->projects_count > 0)

                                    {{ Str::plural(
                                        'project',
                                        $category->projects_count
                                    ) }}

                                    assigned

                                @else

                                    No projects assigned

                                @endif

                            </small>

                        </div>


                        <div class="category-card__footer">

                            <a
                                href="{{ route(
                                    'categories.edit',
                                    $category
                                ) }}"
                                class="category-card__edit"
                            >
                                Edit Category
                            </a>


                            @can('delete', $category)

                                @if ($category->projects_count === 0)

                                    <button
                                        type="button"
                                        wire:click="delete({{ $category->id }})"
                                        wire:confirm="Delete this category?"
                                        class="project-delete"
                                    >
                                        Delete
                                    </button>

                                @else

                                    <span
                                        class="category-card__locked"
                                        title="Category is assigned to projects"
                                    >
                                        In Use
                                    </span>

                                @endif

                            @endcan

                        </div>

                    </article>

                @endforeach

            </div>


            <div class="category-pagination">

                {{ $categories->links() }}

            </div>

        @else

            <div class="category-empty">

                <div class="category-empty__icon">
                    ◇
                </div>

                <h3>
                    No categories found
                </h3>

                <p>

                    @if ($search)

                        No categories match
                        <strong>
                            "{{ $search }}"
                        </strong>.

                    @else

                        Create your first category to start
                        organizing your portfolio.

                    @endif

                </p>


                @if ($search)

                    <button
                        type="button"
                        wire:click="$set('search', '')"
                        class="project-secondary-button"
                    >
                        Clear Search
                    </button>

                @else

                    @can('create', App\Models\Category::class)

                        <a
                            href="{{ route('categories.create') }}"
                            class="btn-primary"
                        >
                            + Create Category
                        </a>

                    @endcan

                @endif

            </div>

        @endif

    </section>

</div>