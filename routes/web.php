<?php

use App\Http\Controllers\UploadController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [UploadController::class, 'index'])->name('upload.index');
Route::post('/uploads', [UploadController::class, 'store'])->name('upload.store');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/uploads/{upload}', [DashboardController::class, 'show'])->name('dashboard.show');
