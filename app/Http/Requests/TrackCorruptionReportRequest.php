<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackCorruptionReportRequest extends FormRequest
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
            'tracking_code' => ['required', 'string', 'size:12', 'alpha_num'],
        ];
    }
}
