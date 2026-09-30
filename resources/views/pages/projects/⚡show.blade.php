<?php

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.public')] class extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        abort_unless(
            $project->status === 'published'
                && $project->published_at !== null
                && $project->published_at->isPast(),
            404
        );

        $this->project = $project->load([
            'categories',
        ]);
    }

    public function render()
    {
        $relatedProjects = Project::query()
            ->with('categories')
            ->where('id', '!=', $this->project->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas(
                'categories',
                fn (Builder $query) =>
                    $query->whereIn(
                        'categories.id',
                        $this->project
                            ->categories
                            ->pluck('id')
                    )
            )
            ->latest('published_at')
            ->limit(3)
            ->get();

        return $this->view([
            'relatedProjects' => $relatedProjects,
        ]);
    }
};
?>

@php

    $image = $project->featured_image
        ? \Illuminate\Support\Facades\Storage::url(
            $project->featured_image
        )
        : asset(
            'images/project-placeholder.jpg'
        );

@endphp

<div class="project-show">

    {{-- HERO --}}

    <section class="project-show__hero">

        <div class="project-show__hero-glow"></div>

        <div class="project-show__hero-grid"></div>

        <div class="public-container">

            <a
                href="{{ route('home') }}"
                class="project-show__back"
            >
                ← Back to Portfolio
            </a>

            <div class="project-show__hero-content">

                <div class="project-show__eyebrow">
                    Portfolio Project
                </div>

                <h1>
                    {{ $project->title }}
                </h1>

                <p>
                    {{ $project->short_description }}
                </p>

                <div class="project-show__categories">

                    @foreach ($project->categories as $category)

                        <span>
                            {{ $category->name }}
                        </span>

                    @endforeach

                </div>

            </div>

        </div>

    </section>


    {{-- FEATURED IMAGE --}}

    <section class="project-show__visual">

        <div class="public-container">

            <div class="project-show__image-frame">

                <div class="project-show__image-glow"></div>

                <img
                    src="{{ $image }}"
                    alt="{{ $project->title }}"
                >

                <div class="project-show__image-sheen"></div>

            </div>

        </div>

    </section>


    {{-- CONTENT --}}

    <section class="project-show__content">

        <div class="public-container">

            <div class="project-show__content-grid">

                <article class="project-show__description">

                    <span class="public-section-eyebrow">
                        Project Overview
                    </span>

                    <h2>
                        Building the
                        <span class="gradient-text">
                            Experience
                        </span>
                    </h2>

                    <div class="project-show__body">

                        {!! nl2br(e($project->description)) !!}

                    </div>

                </article>


                <aside class="project-show__details">

                    <div class="project-show__detail-card">

                        <span>
                            Published
                        </span>

                        <strong>
                            {{ $project->published_at->format('F j, Y') }}
                        </strong>

                    </div>

                    <div class="project-show__detail-card">

                        <span>
                            Categories
                        </span>

                        <strong>
                            {{ $project->categories->count() }}
                        </strong>

                    </div>

                    <div class="project-show__detail-card">

                        <span>
                            Project Status
                        </span>

                        <strong>
                            Published
                        </strong>

                    </div>

                </aside>

            </div>

        </div>

    </section>


    {{-- RELATED PROJECTS --}}

    @if ($relatedProjects->isNotEmpty())

        <section class="project-related">

            <div class="public-container">

                <div class="project-related__heading">

                    <span class="public-section-eyebrow">
                        Continue Exploring
                    </span>

                    <h2>
                        Related
                        <span class="gradient-text">
                            Projects
                        </span>
                    </h2>

                </div>


                <div class="project-related__grid">

                    @foreach ($relatedProjects as $related)

                        @php

                            $relatedImage = $related->featured_image
                                ? \Illuminate\Support\Facades\Storage::url(
                                    $related->featured_image
                                )
                                : asset(
                                    'images/project-placeholder.jpg'
                                );

                        @endphp

                        <a
                            href="{{ route(
                                'projects.show',
                                $related->slug
                            ) }}"
                            class="related-project-card"
                        >

                            <div class="related-project-card__image">

                                <img
                                    src="{{ $relatedImage }}"
                                    alt="{{ $related->title }}"
                                    loading="lazy"
                                >

                                <span>
                                    Explore →
                                </span>

                            </div>

                            <div class="related-project-card__body">

                                <h3>
                                    {{ $related->title }}
                                </h3>

                                <p>
                                    {{ $related->short_description }}
                                </p>

                            </div>

                        </a>

                    @endforeach

                </div>

            </div>

        </section>

    @endif


    <x-public-footer />

</div>