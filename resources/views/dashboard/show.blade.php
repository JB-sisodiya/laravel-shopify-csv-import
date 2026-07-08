@extends('layouts.app')

@section('title', 'Upload Details #' . $upload->id)

@section('page-styles')
<style>
    .details-container {
        max-width: 1200px;
        margin: 0 auto;
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
    
    .info-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        color: #8b95b0;
    }
    
    .info-value {
        font-size: 0.95rem;
        font-weight: 500;
        color: #1a1f36;
    }

    .level-debug { background-color: #e2e8f0; color: #475569; }
    .level-info { background-color: #dbeafe; color: #1e40af; }
    .level-notice { background-color: #e0f2fe; color: #0369a1; }
    .level-warning { background-color: #fef3c7; color: #92400e; }
    .level-error, .level-critical, .level-alert, .level-emergency { background-color: #fee2e2; color: #991b1b; }
</style>
@endsection

@section('content')
<div class="details-container">
    
    {{-- Breadcrumbs & Navigation Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Upload Details</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800" style="font-weight: 800; letter-spacing: -0.5px;">Upload #{{ $upload->id }}</h1>
        </div>
        <a href="{{ route('dashboard.index') }}" class="btn btn-outline-secondary btn-sm py-2 px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    {{-- Upload Information Card --}}
    <div class="app-card mb-4">
        <div class="app-card-header">
            <h5 class="mb-0" style="font-weight: 700; color: #1a1f36;">Upload Information</h5>
        </div>
        <div class="app-card-body">
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="info-label">Original Filename</div>
                    <div class="info-value text-break">{{ $upload->original_filename }}</div>
                </div>
                {{-- <div class="col-md-3 col-sm-6">
                    <div class="info-label">Stored Filename</div>
                    <div class="info-value text-break text-muted">{{ $upload->stored_filename }}</div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="info-label">File Path</div>
                    <div class="info-value text-break text-muted"><code style="font-size:0.85rem;">{{ $upload->file_path }}</code></div>
                </div> --}}
                <div class="col-md-3 col-sm-6">
                    <div class="info-label">Status</div>
                    <div>
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
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="info-label">Total Rows</div>
                    <div class="info-value">{{ number_format($upload->total_rows) }}</div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="info-label">Processed Rows</div>
                    <div class="info-value">{{ number_format($upload->processed_rows) }}</div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="info-label">Successful Syncs</div>
                    <div class="info-value text-success fw-bold">{{ number_format($upload->successful_rows) }}</div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="info-label">Failed Syncs</div>
                    <div class="info-value text-danger fw-bold">{{ number_format($upload->failed_rows) }}</div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="info-label">Started At</div>
                    <div class="info-value text-muted">{{ $upload->started_at ? $upload->started_at->format('Y-m-d H:i:s') : '—' }}</div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="info-label">Completed At</div>
                    <div class="info-value text-muted">{{ $upload->completed_at ? $upload->completed_at->format('Y-m-d H:i:s') : '—' }}</div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="info-label">Uploaded At</div>
                    <div class="info-value text-muted">{{ $upload->created_at->format('Y-m-d H:i:s') }}</div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="info-label">Processing Progress</div>
                    <div class="mt-1">
                        @php
                            $pct = $upload->progress_percentage;
                            $barClass = $upload->status === \App\Enums\UploadStatus::Failed ? 'bg-danger' : 'bg-primary';
                        @endphp
                        <div class="progress" style="height: 16px; border-radius: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated {{ $barClass }}" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                <span style="font-size:0.75rem; font-weight:700;">{{ $pct }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Content for Related Lists --}}
    <ul class="nav nav-tabs mb-3" id="detailsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products-content" type="button" role="tab" aria-controls="products-content" aria-selected="true">
                <i class="bi bi-box-seam me-1"></i> Products ({{ $products->total() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="records-tab" data-bs-toggle="tab" data-bs-target="#records-content" type="button" role="tab" aria-controls="records-content" aria-selected="false">
                <i class="bi bi-clock-history me-1"></i> Import Records ({{ $importRecords->total() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="errors-tab" data-bs-toggle="tab" data-bs-target="#errors-content" type="button" role="tab" aria-controls="errors-content" aria-selected="false">
                <i class="bi bi-exclamation-octagon me-1"></i> Error Logs ({{ $errorLogs->total() }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="detailsTabContent">
        
        {{-- Products Tab --}}
        <div class="tab-pane fade show active" id="products-content" role="tabpanel" aria-labelledby="products-tab">
            <div class="app-card">
                <div class="app-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 120px;">Row Number</th>
                                    <th>Title</th>
                                    <th>SKU</th>
                                    <th>Shopify Product ID</th>
                                    <th class="text-center pe-4" style="width: 150px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $index => $product)
                                    @php
                                        // Retrieve row number from associated import record, or fall back to array index
                                        $rowNum = $product->importRecords()->first()?->row_number ?? ($index + 1);
                                    @endphp
                                    <tr>
                                        <td class="ps-4 fw-semibold text-muted">#{{ $rowNum }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $product->title }}</div>
                                            <div class="small text-muted" style="font-size:0.75rem;">Handle: {{ $product->handle }}</div>
                                        </td>
                                        <td><code>{{ $product->sku ?: '—' }}</code></td>
                                        <td>
                                            @if($product->shopify_product_id)
                                                <span class="text-break" style="font-size:0.85rem;">{{ $product->shopify_product_id }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center pe-4">
                                            @php
                                                $prodStatusClass = match($product->status) {
                                                    \App\Enums\ProductStatus::Pending => 'badge-pending',
                                                    \App\Enums\ProductStatus::Processing => 'badge-processing',
                                                    \App\Enums\ProductStatus::Successful => 'badge-completed',
                                                    \App\Enums\ProductStatus::Failed => 'badge-failed',
                                                };
                                            @endphp
                                            <span class="badge status-badge {{ $prodStatusClass }}">
                                                {{ $product->status->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-box-seam display-4 d-block mb-3" style="color: #cbd5e1;"></i>
                                            No products imported yet for this upload.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($products->hasPages())
                    <div class="app-card-footer d-flex justify-content-center border-top">
                        {{ $products->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Import Records Tab --}}
        <div class="tab-pane fade" id="records-content" role="tabpanel" aria-labelledby="records-tab">
            <div class="app-card">
                <div class="app-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 120px;">Row Number</th>
                                    <th>Action</th>
                                    <th>Status</th>
                                    <th class="pe-4">Error Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($importRecords as $record)
                                    <tr>
                                        <td class="ps-4 fw-semibold text-muted">#{{ $record->row_number }}</td>
                                        <td>
                                            @if($record->action)
                                                <span class="badge bg-light text-dark border fw-semibold">
                                                    {{ $record->action->label() }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $recStatusClass = match($record->status) {
                                                    \App\Enums\ProductStatus::Pending => 'badge-pending',
                                                    \App\Enums\ProductStatus::Processing => 'badge-processing',
                                                    \App\Enums\ProductStatus::Successful => 'badge-completed',
                                                    \App\Enums\ProductStatus::Failed => 'badge-failed',
                                                };
                                            @endphp
                                            <span class="badge status-badge {{ $recStatusClass }}">
                                                {{ $record->status->label() }}
                                            </span>
                                        </td>
                                        <td class="text-danger pe-4" style="font-size: 0.85rem; max-width: 400px; word-break: break-all;">
                                            {{ $record->error_message ?: '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-clock-history display-4 d-block mb-3" style="color: #cbd5e1;"></i>
                                            No import attempts logged yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($importRecords->hasPages())
                    <div class="app-card-footer d-flex justify-content-center border-top">
                        {{ $importRecords->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Error Logs Tab --}}
        <div class="tab-pane fade" id="errors-content" role="tabpanel" aria-labelledby="errors-tab">
            <div class="app-card">
                <div class="app-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 180px;">Time</th>
                                    <th style="width: 120px;">Level</th>
                                    <th>Source</th>
                                    <th class="pe-4">Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($errorLogs as $log)
                                    <tr>
                                        <td class="ps-4 text-nowrap text-muted" style="font-size: 0.85rem;">
                                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                                        </td>
                                        <td>
                                            <span class="badge px-2 py-1 text-uppercase fw-bold level-{{ $log->level->value }}" style="font-size: 0.72rem; border-radius: 4px;">
                                                {{ $log->level->label() }}
                                            </span>
                                        </td>
                                        <td style="font-size: 0.85rem; font-family: monospace;" class="text-muted">
                                            {{ class_basename($log->source ?: 'System') }}
                                        </td>
                                        <td class="text-dark pe-4 text-wrap" style="font-size: 0.88rem; max-width: 500px; word-break: break-all;">
                                            {{ $log->message }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-emoji-smile display-4 d-block mb-3" style="color: #cbd5e1;"></i>
                                            Zero errors logged! Perfect import run.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($errorLogs->hasPages())
                    <div class="app-card-footer d-flex justify-content-center border-top">
                        {{ $errorLogs->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
