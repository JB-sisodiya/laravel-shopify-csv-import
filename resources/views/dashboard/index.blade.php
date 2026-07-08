@extends('layouts.app')

@section('title', 'Imports Dashboard')

@section('page-styles')
<style>
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .table-responsive {
        border-radius: .5rem;
    }
    .badge-pending { background-color: #6c757d; }
    .badge-processing { background-color: #ffc107; color: #212529; }
    .badge-completed { background-color: #198754; }
    .badge-failed { background-color: #dc3545; }
    
    .status-badge {
        font-size: 0.8rem;
        padding: 0.35em 0.65em;
        border-radius: 50rem;
        font-weight: 600;
        text-transform: uppercase;
    }
</style>
@endsection

@section('content')
<div class="dashboard-container">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800" style="font-weight: 800; letter-spacing: -0.5px;">Import Dashboard</h1>
            <p class="text-muted mb-0">Manage and monitor your product import history and processing status.</p>
        </div>
        <a href="{{ route('upload.index') }}" class="btn btn-primary d-flex align-items-center gap-2" style="background: var(--brand-primary); border: none;">
            <i class="bi bi-file-earmark-arrow-up"></i> Upload New CSV
        </a>
    </div>

    <div class="app-card">
        <div class="app-card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0" style="font-weight: 700; color: #1a1f36;">Upload History</h5>
            <span class="badge bg-secondary">{{ $uploads->total() }} Total Uploads</span>
        </div>

        <div class="app-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Original File Name</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Total Rows</th>
                            <th class="text-end">Processed</th>
                            <th class="text-end">Successful</th>
                            <th class="text-end">Failed</th>
                            <th>Started At</th>
                            <th>Completed At</th>
                            <th>Created At</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($uploads as $upload)
                            <tr>
                                <td class="ps-4 fw-semibold text-muted">#{{ $upload->id }}</td>
                                <td class="fw-semibold text-wrap" style="max-width: 200px; word-break: break-all;">
                                    {{ $upload->original_filename }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusClass = match($upload->status) {
                                            \App\Enums\UploadStatus::Pending => 'badge-pending',
                                            \App\Enums\UploadStatus::Processing => 'badge-processing',
                                            \App\Enums\UploadStatus::Completed => 'badge-completed',
                                            \App\Enums\UploadStatus::Failed => 'badge-failed',
                                        };
                                    @endphp
                                    <span class="badge status-badge {{ $statusClass }}">
                                        {{ $upload->status->label() }}
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($upload->total_rows) }}</td>
                                <td class="text-end text-muted">{{ number_format($upload->processed_rows) }}</td>
                                <td class="text-end text-success fw-semibold">{{ number_format($upload->successful_rows) }}</td>
                                <td class="text-end text-danger fw-semibold">{{ number_format($upload->failed_rows) }}</td>
                                <td class="text-nowrap text-muted" style="font-size: 0.85rem;">
                                    {{ $upload->started_at ? $upload->started_at->format('Y-m-d H:i:s') : '—' }}
                                </td>
                                <td class="text-nowrap text-muted" style="font-size: 0.85rem;">
                                    {{ $upload->completed_at ? $upload->completed_at->format('Y-m-d H:i:s') : '—' }}
                                </td>
                                <td class="text-nowrap text-muted" style="font-size: 0.85rem;">
                                    {{ $upload->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="text-center pe-4">
                                    <a href="{{ route('dashboard.show', $upload) }}" class="btn btn-sm btn-outline-primary py-1 px-3" style="font-size: 0.8rem; font-weight: 600;">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="bi bi-folder2-open display-4 d-block mb-3" style="color: #cbd5e1;"></i>
                                    No CSV uploads found. Start by uploading a file.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($uploads->hasPages())
            <div class="app-card-footer d-flex justify-content-center border-top">
                {{ $uploads->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
