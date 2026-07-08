@extends('layouts.app')

@section('title', 'Upload CSV')

@section('page-styles')
<style>
    .upload-card {
        max-width: 500px;
        margin: 40px auto;
    }
    .drop-zone {
        border: 2px dashed #c7d0eb;
        border-radius: 12px;
        background: #f8f9ff;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }
    .drop-zone:hover, .drop-zone.dragover {
        border-color: var(--brand-primary);
        background: #eef0fc;
    }
    .drop-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }
    .btn-upload {
        background: linear-gradient(135deg, var(--brand-primary), var(--brand-dark));
        color: #fff;
        font-weight: 600;
        padding: 12px;
        border-radius: 8px;
        border: none;
        width: 100%;
        margin-top: 20px;
        transition: all 0.2s ease;
    }
    .btn-upload:hover:not(:disabled) {
        opacity: 0.9;
        transform: translateY(-1px);
    }
    .btn-upload:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
@endsection

@section('content')
<div class="upload-card">
    <div class="app-card">
        <div class="app-card-header">
            <h1 class="h5 mb-0">Upload CSV</h1>
        </div>
        <div class="app-card-body">
            @if($errors->any())
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="upload-form" action="{{ route('upload.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="drop-zone" id="drop-zone">
                    <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv,application/csv,text/plain">
                    <div id="dz-text">
                        <i class="bi bi-cloud-upload d-block mb-2" style="font-size: 2rem; color: var(--brand-primary);"></i>
                        <strong>Drag & drop CSV</strong> or click to browse
                        <div class="mt-2 text-muted" style="font-size: 0.8rem; color: #6b7280 !important;">
                            Accepted format: .csv &middot; Max size: 10 MB
                        </div>
                    </div>
                </div>

                <button type="submit" id="submit-btn" class="btn-upload" disabled>
                    Upload CSV
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const fileInput = document.getElementById('csv_file');
    const dropZone = document.getElementById('drop-zone');
    const submitBtn = document.getElementById('submit-btn');
    const dzText = document.getElementById('dz-text');

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            dzText.innerHTML = `<i class="bi bi-file-earmark-check d-block mb-2" style="font-size: 2rem; color: #22c55e;"></i><strong>${this.files[0].name}</strong>`;
            submitBtn.disabled = false;
        } else {
            dzText.innerHTML = '<i class="bi bi-cloud-upload d-block mb-2" style="font-size: 2rem; color: var(--brand-primary);"></i><strong>Drag & drop CSV</strong> or click to browse<div class="mt-2 text-muted" style="font-size: 0.8rem; color: #6b7280 !important;">Accepted format: .csv &middot; Max size: 10 MB</div>';
            submitBtn.disabled = true;
        }
    });

    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files[0]) {
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush
