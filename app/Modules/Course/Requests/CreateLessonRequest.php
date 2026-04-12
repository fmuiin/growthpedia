<?php

declare(strict_types=1);

namespace App\Modules\Course\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLessonRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'content_type' => ['required', 'string', 'in:text,video,mixed'],
            'content_body' => ['nullable', 'string', 'required_if:content_type,text,mixed'],
            'video_url' => ['nullable', 'url', 'max:500', 'required_if:content_type,video,mixed'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
