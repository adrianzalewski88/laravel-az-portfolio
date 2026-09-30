<?php

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public Category $category;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public function mount(Category $category): void
    {
        $this->authorize('update', $category);

        $this->category = $category;

        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = $category->description ?? '';
    }

    public function updatedName(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function save(): void
    {
        $this->authorize('update', $this->category);

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:categories,name,' . $this->category->id,
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:categories,slug,' . $this->category->id,
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $this->category->update($validated);

        session()->flash(
            'success',
            'Category updated successfully.'
        );
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->category);

        $this->category->delete();

        session()->flash(
            'success',
            'Category deleted successfully.'
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
                Edit Category
            </h1>

            <p class="project-page-description">
                Update the category information and organization.
            </p>

        </div>

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

                    <div>

                        <h2>
                            Category Details
                        </h2>

                        <p>
                            Update the category information.
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
                            Actions
                        </h2>

                        <p>
                            Save changes or remove this category.
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
                            Save Changes
                        </span>

                        <span wire:loading>
                            Saving...
                        </span>
                    </button>

                    <a
                        href="{{ route('categories.index') }}"
                        class="project-secondary-button"
                    >
                        Cancel
                    </a>

                    <button
                        type="button"
                        wire:click="delete"
                        wire:confirm="Delete this category? Projects using this category will lose the category association."
                        class="project-delete"
                    >
                        Delete Category
                    </button>

                </div>

            </section>

        </aside>

    </form>

</div>