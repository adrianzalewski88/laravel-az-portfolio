@extends('layouts.app')

@section('title', 'Dashboard | AZ Portfolio')

@section('content')

<div class="dashboard-container">

    <header class="dashboard-header">

        <div>

            <p class="dashboard-eyebrow">
                AZ Portfolio
            </p>

            <h1 class="dashboard-title">
                Dashboard
            </h1>

            <p class="dashboard-description">
                Welcome back, {{ auth()->user()->name }}.
                Manage your portfolio projects and content.
            </p>

        </div>


        <a
            href="{{ route('projects.create') }}"
            class="btn-primary"
        >
            Add Project
        </a>

    </header>


    <section class="dashboard-stats">

        <article class="dashboard-stat">

            <span class="dashboard-stat__label">
                Projects
            </span>

            <strong class="dashboard-stat__value">
                {{ \App\Models\Project::count() }}
            </strong>

        </article>


        <article class="dashboard-stat">

            <span class="dashboard-stat__label">
                Published
            </span>

            <strong class="dashboard-stat__value">
                {{ \App\Models\Project::where('status', 'published')->count() }}
            </strong>

        </article>


        <article class="dashboard-stat">

            <span class="dashboard-stat__label">
                Categories
            </span>

            <strong class="dashboard-stat__value">
                {{ \App\Models\Category::count() }}
            </strong>

        </article>

    </section>


    <section class="dashboard-panel">

        <div class="dashboard-panel__header">

            <div>

                <p class="dashboard-eyebrow">
                    Content
                </p>

                <h2>
                    Portfolio Projects
                </h2>

            </div>


            <a
                href="{{ route('projects.index') }}"
                class="dashboard-panel__link"
            >
                View all
            </a>

        </div>


        <div class="dashboard-empty">

            <h3>
                Your portfolio content lives here.
            </h3>

            <p>
                Create and manage projects that will eventually
                be delivered to the React portfolio through the
                Laravel API.
            </p>

        </div>

    </section>

</div>

@endsection