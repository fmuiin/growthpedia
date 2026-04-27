<?php

declare(strict_types=1);

namespace App\Modules\Branding\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreatorProfileRequest extends FormRequest
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
            'display_name' => ['sometimes', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'avatar_url' => ['nullable', 'url', 'max:500'],
            'expertise' => ['nullable', 'string', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'social_links.twitter' => ['nullable', 'string', 'url', 'max:500'],
            'social_links.linkedin' => ['nullable', 'string', 'url', 'max:500'],
            'social_links.youtube' => ['nullable', 'string', 'url', 'max:500'],
            'social_links.website' => ['nullable', 'string', 'url', 'max:500'],
            'featured_course_ids' => ['nullable', 'array'],
            'featured_course_ids.*' => ['integer', 'exists:courses,id'],
        ];
    }

    /**
     * Additional validation to ensure featured courses are published.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $featuredCourseIds = $this->input('featured_course_ids');

            if (! is_array($featuredCourseIds) || empty($featuredCourseIds)) {
                return;
            }

            $publishedCount = \Illuminate\Support\Facades\DB::table('courses')
                ->whereIn('id', $featuredCourseIds)
                ->where('status', 'published')
                ->count();

            if ($publishedCount !== count($featuredCourseIds)) {
                $validator->errors()->add(
                    'featured_course_ids',
                    'Featured courses must be published.'
                );
            }
        });
    }
}
