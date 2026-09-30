<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $image = $this->featured_image;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,

            'shortDescription' => $this->short_description,
            'body' => $this->description,

            'featuredImage' => $image
                ? (
                    filter_var($image, FILTER_VALIDATE_URL)
                        ? $image
                        : Storage::disk('public')->url($image)
                )
                : null,

            'category' => $this->categories->first()?->name,

            'categories' => CategoryResource::collection(
                $this->whenLoaded('categories')
            ),

            'publishedAt' => $this->published_at?->toIso8601String(),
        ];
    }
}