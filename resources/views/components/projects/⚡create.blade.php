<?php

use App\Models\Category;
use App\Models\Project;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithFileUploads;

    public string $title = '';

    public string $slug = '';

    public string $short_description = '';

    public string $description = '';

    public string $status = 'draft';

    public ?string $published_at = null;

    public array $selectedCategories = [];

    public $featured_image = null;

    public function updatedTitle(): void
    {
        $this->slug = Str::slug($this->title);
    }

    public function save()
    {
        $this->authorize('create', Project::class);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:projects,slug'],
            'short_description' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => [
                'nullable',
                'date',
                'required_if:status,published',
            ],
            'selectedCategories' => ['array'],
            'selectedCategories.*' => ['integer', 'exists:categories,id'],
            'featured_image' => [
                'nullable',
                'image',
                'max:5120',
            ],
        ]);

        $project = Project::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'short_description' => $validated['short_description'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? null,
        ]);

        if ($this->featured_image) {
            $path = $this->featured_image->store(
                'projects',
                'public'
            );

            $project->update([
                'featured_image' => $path,
            ]);
        }

        $project->categories()->sync(
            $validated['selectedCategories'] ?? []
        );

        session()->flash(
            'success',
            'Project created successfully.'
        );

        return $this->redirect(
            route('projects.edit', $project),
            navigate: true
        );
    }

    public function render()
    {
        return view(
            'components.projects.⚡create',
            [
                'categories' => Category::query()
                    ->orderBy('name')
                    ->get(),
            ]
        );
    }
};
?>

<div class="project-container">

    <header class="project-page-header">

        <div>

            <p class="project-eyebrow">
                Portfolio
            </p>

            <h1 class="project-page-title">
                Create Project
            </h1>

            <p class="project-page-description">
                Add a project to your portfolio and make it
                available to the React application through the API.
            </p>

        </div>

        <a
            href="{{ route('projects.index') }}"
            class="project-secondary-button"
        >
            Back to Projects
        </a>

    </header>


    @if (session('success'))

        <div class="project-alert project-alert--success">
            {{ session('success') }}
        </div>

    @endif


    <form
        wire:submit="save"
        class="project-form"
    >

        <div class="project-form__main">

            <section class="project-panel">

                <div class="project-panel__header">

                    <p class="project-eyebrow">
                        Project Information
                    </p>

                    <h2>
                        Basic Details
                    </h2>

                </div>


                <div class="project-field">

                    <label for="title">
                        Project Title
                    </label>

                    <input
                        id="title"
                        type="text"
                        wire:model.live="title"
                        class="project-input"
                        placeholder="My Laravel Portfolio"
                    >

                    @error('title')
                        <span class="project-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                <div class="project-field">

                    <label for="slug">
                        Slug
                    </label>

                    <input
                        id="slug"
                        type="text"
                        wire:model="slug"
                        class="project-input"
                        placeholder="my-laravel-portfolio"
                    >

                    @error('slug')
                        <span class="project-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                <div class="project-field">

                    <label for="short_description">
                        Short Description
                    </label>

                    <textarea
                        id="short_description"
                        wire:model="short_description"
                        class="project-input"
                        rows="4"
                        placeholder="A brief description of the project."
                    ></textarea>

                    @error('short_description')
                        <span class="project-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                <div class="project-field">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        wire:model="description"
                        class="project-input"
                        rows="12"
                        placeholder="Describe the project, technologies, architecture, and important implementation details."
                    ></textarea>

                    @error('description')
                        <span class="project-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>

            </section>


            <section class="project-panel">

                <div class="project-panel__header">

                    <p class="project-eyebrow">
                        Featured Image
                    </p>

                    <h2>
                        Project Artwork
                    </h2>

                </div>


                <div class="project-field">

                    <label for="featured_image">
                        Featured Image
                    </label>

                    <input
                        id="featured_image"
                        type="file"
                        wire:model="featured_image"
                        class="project-input"
                        accept="image/*"
                    >

                    <p class="project-help">
                        Optional. Maximum file size: 5 MB.
                    </p>

                    @error('featured_image')
                        <span class="project-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                @if ($featured_image)

                    <div class="project-image-preview">

                        <img
                            src="{{ $featured_image->temporaryUrl() }}"
                            alt="Featured image preview"
                        >

                    </div>

                @endif

            </section>

        </div>


        <aside class="project-form__sidebar">

            <section class="project-panel">

                <div class="project-panel__header">

                    <p class="project-eyebrow">
                        Publishing
                    </p>

                    <h2>
                        Status
                    </h2>

                </div>


                <div class="project-field">

                    <label for="status">
                        Project Status
                    </label>

                    <select
                        id="status"
                        wire:model="status"
                        class="project-input"
                    >
                        <option value="draft">
                            Draft
                        </option>

                        <option value="published">
                            Published
                        </option>
                    </select>

                    @error('status')
                        <span class="project-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                <div class="project-field">

                    <label for="published_at">
                        Published At
                    </label>

                    <input
                        id="published_at"
                        type="datetime-local"
                        wire:model="published_at"
                        class="project-input"
                    >

                    @error('published_at')
                        <span class="project-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>

            </section>


            <section class="project-panel">

                <div class="project-panel__header">

                    <p class="project-eyebrow">
                        Organization
                    </p>

                    <h2>
                        Categories
                    </h2>

                </div>


                <div class="project-category-list">

                    @foreach ($categories as $category)

                        <label class="project-category">

                            <input
                                type="checkbox"
                                wire:model="selectedCategories"
                                value="{{ $category->id }}"
                            >

                            <span>
                                {{ $category->name }}
                            </span>

                        </label>

                    @endforeach

                </div>


                @error('selectedCategories.*')
                    <span class="project-error">
                        {{ $message }}
                    </span>
                @enderror

            </section>


            <button
                type="submit"
                class="btn-primary project-submit"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>
                    Create Project
                </span>

                <span wire:loading>
                    Creating...
                </span>
            </button>

        </aside>

    </form>

</div>