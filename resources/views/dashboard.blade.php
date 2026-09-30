
@php
    use App\Models\Category;
    use App\Models\Project;
    use App\Models\User;
    use Illuminate\Support\Facades\Storage;

    $now = now();

    // Project statistics
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

    $projectsWithoutImages = Project::query()
        ->where(function ($query) {
            $query->whereNull('featured_image')
                ->orWhere('featured_image', '');
        })
        ->count();

    $publishingRate = $totalProjects > 0
        ? round(($publishedProjects / $totalProjects) * 100)
        : 0;

    // Category statistics
    $totalCategories = Category::count();

    $categoriesWithProjects = Category::query()
        ->has('projects')
        ->count();

    $unusedCategories = $totalCategories - $categoriesWithProjects;

    // User statistics
    $totalUsers = User::count();

    $totalAdmins = User::role('admin')->count();

    // Recently published projects
    $recentProjects = Project::query()
        ->with(['categories', 'user'])
        ->where('status', 'published')
        ->whereNotNull('published_at')
        ->where('published_at', '<=', $now)
        ->orderByDesc('published_at')
        ->limit(6)
        ->get();

    // Record distribution for the doughnut chart
    $totalRecords = $totalProjects + $totalCategories + $totalUsers;

    $projectPercent = $totalRecords > 0
        ? ($totalProjects / $totalRecords) * 100
        : 0;

    $categoryPercent = $totalRecords > 0
        ? ($totalCategories / $totalRecords) * 100
        : 0;

    $userPercent = $totalRecords > 0
        ? ($totalUsers / $totalRecords) * 100
        : 0;

    $projectStop = $projectPercent;
    $categoryStop = $projectPercent + $categoryPercent;

    $chartGradient = $totalRecords > 0
        ? sprintf(
            'conic-gradient(
                #1f69ff 0%% %.4f%%,
                #a275ff %.4f%% %.4f%%,
                #f2b46b %.4f%% 100%%
            )',
            $projectStop,
            $projectStop,
            $categoryStop,
            $categoryStop
        )
        : 'conic-gradient(#273248 0% 100%)';

    // Image uploaded to your public/images directory
    $placeholderImage = asset('images/project-placeholder.jpg');
@endphp

@extends('layouts.app')

@section('title', 'Dashboard | AZ Portfolio')

@section('content')

<div class="dashboard-container">

    {{-- Welcome header --}}

    <header class="dashboard-header">

        <div>
            <p class="dashboard-eyebrow">
                AZ Portfolio / Command Center
            </p>

            <h1 class="dashboard-title">
                Dashboard<span class="dashboard-title__dot">.</span>
            </h1>

            <p class="dashboard-description">
                Welcome back, {{ auth()->user()->name }}.
                Here's what's happening across your portfolio CMS.
            </p>
        </div>

        <div class="dashboard-header__status">
            <span class="dashboard-status__dot"></span>
            CMS Online
        </div>

    </header>


    {{-- Primary statistics --}}

    <section
        class="dashboard-stats"
        aria-label="Portfolio statistics"
    >

        <article class="dashboard-stat dashboard-stat--blue">
            <div class="dashboard-stat__top">
                <span class="dashboard-stat__label">
                    Total Projects
                </span>
                <span class="dashboard-stat__symbol">◈</span>
            </div>

            <strong class="dashboard-stat__value">
                {{ number_format($totalProjects) }}
            </strong>

            <span class="dashboard-stat__detail">
                All portfolio entries
            </span>

            <div class="dashboard-stat__bottom">
                <a href="{{ route('projects.index') }}">
                    View projects &rarr;
                </a>
            </div>
        </article>


        <article class="dashboard-stat dashboard-stat--green">
            <div class="dashboard-stat__top">
                <span class="dashboard-stat__label">
                    Published Projects
                </span>
                <span class="dashboard-stat__symbol">✓</span>
            </div>

            <strong class="dashboard-stat__value">
                {{ number_format($publishedProjects) }}
            </strong>

            <span class="dashboard-stat__detail">
                {{ $publishingRate }}% of all projects
            </span>

            <div
                class="dashboard-stat__progress"
                role="progressbar"
                aria-valuenow="{{ $publishingRate }}"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="Project publishing rate"
            >
                <span style="width: {{ $publishingRate }}%"></span>
            </div>
        </article>


        <article class="dashboard-stat dashboard-stat--purple">
            <div class="dashboard-stat__top">
                <span class="dashboard-stat__label">
                    Categories
                </span>
                <span class="dashboard-stat__symbol">◇</span>
            </div>

            <strong class="dashboard-stat__value">
                {{ number_format($totalCategories) }}
            </strong>

            <span class="dashboard-stat__detail">
                {{ $categoriesWithProjects }} in use
            </span>

            <div class="dashboard-stat__bottom">
                <a href="{{ route('categories.index') }}">
                    View categories &rarr;
                </a>
            </div>
        </article>


        <article class="dashboard-stat dashboard-stat--orange">
            <div class="dashboard-stat__top">
                <span class="dashboard-stat__label">
                    CMS Users
                </span>
                <span class="dashboard-stat__symbol">♙</span>
            </div>

            <strong class="dashboard-stat__value">
                {{ number_format($totalUsers) }}
            </strong>

            <span class="dashboard-stat__detail">
                {{ $totalAdmins }} administrators
            </span>

            <div class="dashboard-stat__bottom">
                <a href="{{ route('users.index') }}">
                    Manage users &rarr;
                </a>
            </div>
        </article>

    </section>


    {{-- Secondary statistics --}}

    <section
        class="dashboard-mini-stats"
        aria-label="Additional statistics"
    >

        <article class="dashboard-mini-stat">
            <span class="dashboard-mini-stat__label">
                Draft Projects
            </span>
            <strong>{{ number_format($draftProjects) }}</strong>
            <span class="dashboard-mini-stat__note">
                Not yet published
            </span>
        </article>

        <article class="dashboard-mini-stat">
            <span class="dashboard-mini-stat__label">
                Scheduled
            </span>
            <strong>{{ number_format($scheduledProjects) }}</strong>
            <span class="dashboard-mini-stat__note">
                Future publication dates
            </span>
        </article>

        <article class="dashboard-mini-stat">
            <span class="dashboard-mini-stat__label">
                Missing Images
            </span>
            <strong>{{ number_format($projectsWithoutImages) }}</strong>
            <span class="dashboard-mini-stat__note">
                Projects without featured images
            </span>
        </article>

        <article class="dashboard-mini-stat">
            <span class="dashboard-mini-stat__label">
                Unused Categories
            </span>
            <strong>{{ number_format($unusedCategories) }}</strong>
            <span class="dashboard-mini-stat__note">
                Categories without projects
            </span>
        </article>

    </section>


    {{-- Chart and quick actions --}}

    <div class="dashboard-overview">

        <section class="dashboard-panel dashboard-chart-panel">

            <div class="dashboard-panel__header">
                <div>
                    <p class="dashboard-panel__eyebrow">
                        Database Overview
                    </p>

                    <h2>Content Distribution</h2>

                    <p>
                        Projects, categories and user accounts
                        stored in your CMS.
                    </p>
                </div>
            </div>

            <div class="dashboard-chart-layout">

                <div
                    class="dashboard-doughnut"
                    style="--chart-gradient: {{ $chartGradient }};"
                    role="img"
                    aria-label="{{ $totalProjects }} projects, {{ $totalCategories }} categories and {{ $totalUsers }} users"
                >
                    <div class="dashboard-doughnut__center">
                        <strong>
                            {{ number_format($totalRecords) }}
                        </strong>

                        <span>Total Records</span>
                    </div>
                </div>

                <div class="dashboard-chart-legend">

                    <div class="dashboard-chart-legend__item">
                        <span class="dashboard-chart-legend__color dashboard-chart-legend__color--blue"></span>

                        <div>
                            <strong>Projects</strong>
                            <span>{{ round($projectPercent, 1) }}%</span>
                        </div>

                        <b>{{ $totalProjects }}</b>
                    </div>

                    <div class="dashboard-chart-legend__item">
                        <span class="dashboard-chart-legend__color dashboard-chart-legend__color--purple"></span>

                        <div>
                            <strong>Categories</strong>
                            <span>{{ round($categoryPercent, 1) }}%</span>
                        </div>

                        <b>{{ $totalCategories }}</b>
                    </div>

                    <div class="dashboard-chart-legend__item">
                        <span class="dashboard-chart-legend__color dashboard-chart-legend__color--orange"></span>

                        <div>
                            <strong>Users</strong>
                            <span>{{ round($userPercent, 1) }}%</span>
                        </div>

                        <b>{{ $totalUsers }}</b>
                    </div>

                </div>

            </div>

        </section>


        <section class="dashboard-panel dashboard-actions-panel">

            <div class="dashboard-panel__header">
                <div>
                    <p class="dashboard-panel__eyebrow">
                        Workspace
                    </p>

                    <h2>Quick Actions</h2>

                    <p>
                        Jump straight into managing your CMS.
                    </p>
                </div>
            </div>

            <div class="dashboard-actions">

                <a
                    href="{{ route('projects.create') }}"
                    class="dashboard-action dashboard-action--primary"
                >
                    <span class="dashboard-action__icon">+</span>

                    <span class="dashboard-action__text">
                        <strong>Add Project</strong>
                        <small>Create a new portfolio entry</small>
                    </span>

                    <span class="dashboard-action__arrow">&rarr;</span>
                </a>

                <a
                    href="{{ route('categories.create') }}"
                    class="dashboard-action"
                >
                    <span class="dashboard-action__icon">◇</span>

                    <span class="dashboard-action__text">
                        <strong>Add Category</strong>
                        <small>Organize your portfolio</small>
                    </span>

                    <span class="dashboard-action__arrow">&rarr;</span>
                </a>

                <a
                    href="{{ route('users.create') }}"
                    class="dashboard-action"
                >
                    <span class="dashboard-action__icon">♙</span>

                    <span class="dashboard-action__text">
                        <strong>Add User</strong>
                        <small>Create a CMS account</small>
                    </span>

                    <span class="dashboard-action__arrow">&rarr;</span>
                </a>

            </div>

        </section>

    </div>


    {{-- Recently published projects --}}

    <section class="dashboard-panel dashboard-recent">

        <div class="dashboard-panel__header">

            <div>
                <p class="dashboard-panel__eyebrow">
                    Portfolio Activity
                </p>

                <h2>Recently Published</h2>

                <p>
                    Your latest published portfolio projects.
                </p>
            </div>

            <a
                href="{{ route('projects.index') }}"
                class="dashboard-panel__link"
            >
                View All Projects &rarr;
            </a>

        </div>


        @if ($recentProjects->isNotEmpty())

            <div class="dashboard-project-grid">

                @foreach ($recentProjects as $project)

                    @php
                        $featuredImage = $project->featured_image
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

                    <article class="dashboard-project">

                        <a
                            href="{{ route('projects.edit', $project) }}"
                            class="dashboard-project__image"
                        >
                            <img
                                src="{{ $featuredImage }}"
                                alt="{{ $project->title }}"
                                loading="lazy"
                                onerror="this.onerror=null;this.src='{{ $placeholderImage }}';"
                            >

                            <span class="dashboard-project__status">
                                Published
                            </span>
                        </a>

                        <div class="dashboard-project__content">

                            <div class="dashboard-project__categories">

                                @forelse ($project->categories->take(3) as $category)

                                    <span>
                                        {{ $category->name }}
                                    </span>

                                @empty

                                    <span>Uncategorized</span>

                                @endforelse

                            </div>

                            <h3>
                                <a href="{{ route('projects.edit', $project) }}">
                                    {{ $project->title }}
                                </a>
                            </h3>

                            <p>
                                {{ Str::limit($project->short_description, 115) }}
                            </p>

                            <div class="dashboard-project__footer">

                                <time datetime="{{ $project->published_at->toDateString() }}">
                                    {{ $project->published_at->format('M j, Y') }}
                                </time>

                                <a href="{{ route('projects.edit', $project) }}">
                                    Edit &rarr;
                                </a>

                            </div>

                        </div>

                    </article>

                @endforeach

            </div>

        @else

            <div class="dashboard-empty">

                <div class="dashboard-empty__icon">◈</div>

                <h3>No published projects yet</h3>

                <p>
                    Once you publish your first project,
                    it will appear here with its featured image,
                    categories and publication date.
                </p>

                <a
                    href="{{ route('projects.create') }}"
                    class="btn-primary"
                >
                    + Create Project
                </a>

            </div>

        @endif

    </section>


    <footer class="dashboard-footer">
        <span>AZ Portfolio CMS</span>
        <span>Laravel · Livewire · MySQL</span>
    </footer>

</div>

@endsection