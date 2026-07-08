<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $uploads = Upload::query()
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('dashboard.index', compact('uploads'));
    }

    public function show(Upload $upload, Request $request): View
    {
        $products = $upload->products()
            ->orderBy('id', 'asc')
            ->paginate(15, ['*'], 'products_page')
            ->withQueryString();

        $importRecords = $upload->importRecords()
            ->orderBy('row_number', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(15, ['*'], 'records_page')
            ->withQueryString();

        $errorLogs = $upload->errorLogs()
            ->orderBy('id', 'desc')
            ->paginate(15, ['*'], 'errors_page')
            ->withQueryString();

        return view('dashboard.show', compact('upload', 'products', 'importRecords', 'errorLogs'));
    }
}
