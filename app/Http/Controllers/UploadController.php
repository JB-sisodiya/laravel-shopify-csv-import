<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UploadRequest;
use App\Jobs\ProcessCsvImportJob;
use App\Services\UploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UploadController extends Controller
{
    public function __construct(
        private readonly UploadService $uploadService,
    ) {}

    public function index(): View
    {
        return view('upload.index');
    }

    public function store(UploadRequest $request): RedirectResponse
    {
        try {
            $upload = $this->uploadService->store($request->file('csv_file'));

            ProcessCsvImportJob::dispatch($upload);

            return redirect()
                ->route('upload.index')
                ->with('success', "✓ \"{$upload->original_filename}\" uploaded successfully and is being processed in the background.");
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('upload.index')
                ->with('error', 'Upload failed: ' . $e->getMessage());
        } catch (\Throwable) {
            return redirect()
                ->route('upload.index')
                ->with('error', 'An unexpected error occurred while uploading your file. Please try again.');
        }
    }
}
