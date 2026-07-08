<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ImportConstants;
use App\Enums\UploadStatus;
use App\Models\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class UploadService
{
    public function store(UploadedFile $file): Upload
    {
        $originalFilename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: 'csv';
        $storedFilename = Str::uuid()->toString() . '.' . $extension;

        $filePath = $file->storeAs(
            ImportConstants::UPLOAD_DIRECTORY,
            $storedFilename,
            ImportConstants::UPLOAD_DISK,
        );

        if ($filePath === false) {
            throw new RuntimeException("Failed to write uploaded file \"{$originalFilename}\" to storage.");
        }

        DB::beginTransaction();
        try {
            $upload = Upload::create([
                'original_filename' => $originalFilename,
                'stored_filename'   => $storedFilename,
                'file_path'         => $filePath,
                'status'            => UploadStatus::Pending,
                'total_rows'        => 0,
                'processed_rows'    => 0,
                'successful_rows'   => 0,
                'failed_rows'       => 0,
            ]);

            DB::commit();
            return $upload;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->deleteStoredFile($filePath);
            throw new RuntimeException("Upload record could not be saved. The file has been removed.", 0, $e);
        }
    }

    private function deleteStoredFile(string $filePath): void
    {
        try {
            Storage::disk(ImportConstants::UPLOAD_DISK)->delete($filePath);
        } catch (\Throwable) {
        }
    }
}
