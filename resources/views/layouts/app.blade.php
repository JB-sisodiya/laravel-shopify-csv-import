<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Laravel') - Shopify Importer</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        :root {
            --brand-primary: #20b1ffff;
            --brand-dark: #171717;
            --surface-bg: #f9fafb;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background-color: var(--surface-bg);
            color: #1f2937;
            min-height: 100vh;
        }

        .navbar {
            background-color: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--brand-dark) !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link {
            color: #4b5563 !important;
            font-weight: 500;
        }

        .nav-link.active {
            color: var(--brand-primary) !important;
        }

        .app-main {
            padding: 40px 0;
        }

        .app-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }

        .app-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .app-card-body {
            padding: 24px;
        }

        .app-card-footer {
            padding: 16px 24px;
            background-color: #f9fafb;
            border-radius: 0 0 12px 12px;
            border-top: 1px solid #e5e7eb;
        }

        .app-footer {
            text-align: center;
            font-size: 0.85rem;
            color: #6b7280;
            padding: 40px 0 20px;
        }

        .pagination {
            --bs-pagination-bg: rgba(255, 255, 255, 0.03);
            --bs-pagination-border-color: rgba(255, 255, 255, 0.08);
            --bs-pagination-color: #9ca3af;
            --bs-pagination-hover-color: #ffffff;
            --bs-pagination-hover-bg: rgba(255, 255, 255, 0.08);
            --bs-pagination-hover-border-color: rgba(255, 255, 255, 0.15);
            --bs-pagination-focus-color: #ffffff;
            --bs-pagination-focus-bg: rgba(255, 255, 255, 0.08);
            --bs-pagination-active-color: #ffffff;
            --bs-pagination-active-bg: var(--brand-primary);
            --bs-pagination-active-border-color: var(--brand-primary);
            --bs-pagination-disabled-color: rgba(255, 255, 255, 0.3);
            --bs-pagination-disabled-bg: rgba(255, 255, 255, 0.01);
            --bs-pagination-disabled-border-color: rgba(255, 255, 255, 0.04);
            margin-bottom: 0;
            gap: 4px;
        }
        .page-link {
            border-radius: 6px;
            padding: 8px 16px;
        }

        @yield('page-styles')
    </style>
    @stack('head')
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="{{ route('upload.index') }}">
            <i class="bi bi-box-seam text-danger" style="font-size: 1.25rem;"></i>
            Shopify Importer
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('upload.index') ? 'active' : '' }}" href="{{ route('upload.index') }}">Upload</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard.*') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">Dashboard</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

@if(session('success') || session('error'))
    <div class="container mt-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    </div>
@endif

<main class="app-main">
    <div class="container">
        @yield('content')
    </div>
</main>

<footer class="app-footer">
    <div class="container">
        Shopify CSV Importer &copy; {{ date('Y') }} &mdash; Laravel {{ app()->version() }}
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
