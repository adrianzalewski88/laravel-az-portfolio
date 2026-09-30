<?php

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public function updatedName(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function save(): void
    {
        $this->authorize('create', Category::class);

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name',
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:categories,slug',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ]);

        Category::create($validated);

        session()->flash(
            'success',
            'Category created successfully.'
        );

        $this->redirect(
            route('categories.index'),
            navigate: true
        );
    }

    public function render()
    {
        return $this->view();
    }
};
?>

<div class="project-container">

    <header class="project-page-header">

        <div>

            <p class="project-eyebrow">
                Portfolio CMS
            </p>

            <h1 class="project-page-title">
                Create Category
            </h1>

            <p class="project-page-description">
                Create a category for organizing and filtering
                your portfolio projects.
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
                            Category Details
                        </h2>

                        <p>
                            Define the name and description
                            visitors will see.
                        </p>

                    </div>

                </div>

                <div class="project-field">

                    <label for="name">
                        Category Name
                    </label>

                    <input
                        id="name"
                        type="text"
                        wire:model.live="name"
                        class="project-input"
                        placeholder="e.g. Laravel"
                    >

                    @error('name')
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
                        placeholder="laravel"
                    >

                    <p class="project-help">
                        Used in URLs and API responses.
                    </p>

                    @error('slug')
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
                        rows="6"
                        placeholder="Describe what this category represents..."
                    ></textarea>

                    @error('description')
                        <span class="project-error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>

            </section>

        </div>

        <aside class="project-form__sidebar">

            <section class="project-panel">

                <div class="project-panel__header">

                    <div>

                        <h2>
                            Publish
                        </h2>

                        <p>
                            Save this category to your portfolio
                            content system.
                        </p>

                    </div>

                </div>

                <div class="project-actions">

                    <button
                        type="submit"
                        class="btn-primary project-submit"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>
                            Create Category
                        </span>

                        <span wire:loading>
                            Creating...
                        </span>
                    </button>

                    <a
                        href="{{ route('categories.index') }}"
                        class="project-secondary-button"
                    >
                        Cancel
                    </a>

                </div>

            </section>

        </aside>

    </form>

</div>