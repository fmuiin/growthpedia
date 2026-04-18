<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlagCommentRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
