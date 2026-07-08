<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Constants\ImportConstants;
use Illuminate\Foundation\Http\FormRequest;

class UploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKilobytes = ImportConstants::MAX_FILE_SIZE_MB * 1024;
        $mimes = implode(',', ['csv', 'txt']);

        return [
            'csv_file' => [
                'required',
                'file',
                "mimes:{$mimes}",
                "max:{$maxKilobytes}",
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'csv_file.required' => 'Please select a CSV file to upload.',
            'csv_file.file'     => 'The uploaded item must be a valid file.',
            'csv_file.mimes'    => 'Only CSV files (.csv) are accepted.',
            'csv_file.max'      => 'The CSV file must not exceed ' . ImportConstants::MAX_FILE_SIZE_MB . ' MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'csv_file' => 'CSV file',
        ];
    }
}
