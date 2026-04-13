<?php

declare(strict_types=1);

namespace App\Modules\Subscription\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePlanRequest extends FormRequest
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
            'new_plan_id' => ['required', 'integer', 'exists:membership_plans,id'],
        ];
    }
}
