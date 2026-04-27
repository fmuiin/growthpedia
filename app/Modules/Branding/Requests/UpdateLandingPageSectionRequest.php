<?php

declare(strict_types=1);

namespace App\Modules\Branding\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLandingPageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section_type' => ['required', 'string', 'in:hero,about,featured_courses,testimonials,cta'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['required', 'integer'],
            'is_visible' => ['required', 'boolean'],
        ];
    }
}
