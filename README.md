# Laravel CSV to Shopify Product Import

## Overview

This application imports products from a CSV file into Shopify using the Shopify Admin GraphQL API.

Features:

- CSV file upload
- Background processing using Laravel Queues
- Product create and update
- Automatic collection assignment
- Import status dashboard
- Product-level success and failure tracking
- Error logging
- Import history

---

# Tech Stack

- Laravel 12
- PHP 8.2+
- MySQL
- Bootstrap 5
- Shopify Admin GraphQL API
- Laravel Queue (Database)

---

# Setup Instructions

## 1. Clone Repository

```bash
git clone https://github.com/JB-sisodiya/laravel-shopify-csv-import.git

cd project-folder
```

## 2. Install PHP Dependencies

```bash
composer install
```

## 3. Create Environment File

```bash
cp .env.example .env
```

## 4. Generate Application Key

```bash
php artisan key:generate
```

## 5. Configure Database

Update your `.env`

```env
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

## 6. Configure Shopify

```env
SHOPIFY_STORE_URL=
SHOPIFY_ACCESS_TOKEN=
SHOPIFY_API_VERSION=
SHOPIFY_COLLECTION_ID=
```

## 7. Run Migrations

```bash
php artisan migrate
```

## 8. Link Storage

```bash
php artisan storage:link
```

## 9. Start Queue Worker

```bash
php artisan queue:work --queue=imports
```

> **Important:** The import jobs are dispatched to the `imports` queue.
> You must pass `--queue=imports` or jobs will never be processed.

## 10. Start Application

```bash
php artisan serve
```

---

# Application Flow

```
Upload CSV

↓

Validate CSV

↓

Create Upload Record

↓

Dispatch Queue Job

↓

Parse CSV

↓

Create / Update Product

↓

Assign Product to Collection

↓

Update Import Status

↓

Dashboard
```

---

# Assumptions / Design Decisions

- Products are identified by their **Handle**.
- If a product already exists, it is updated instead of creating a duplicate.
- Only **Handle** and **Title** are treated as required CSV columns.
- Queue processing is used to prevent long-running HTTP requests.
- Import continues even if individual rows fail.
- All product import events are logged.

---

# Testing Instructions

1. Configure Shopify credentials in `.env`.
2. Start the queue worker.

```bash
php artisan queue:work --queue=imports
```

3. Open the application.

```
http://127.0.0.1:8000
```

4. Upload the provided sample CSV.

5. Verify:

- Upload created successfully
- Queue processed the file
- Products created or updated in Shopify
- Products assigned to the configured collection
- Dashboard updated correctly
- Success and failure counts displayed correctly

---

# Notes

- Uses the latest Shopify Admin GraphQL API.
- CSV processing runs asynchronously using Laravel Queues.
- Failed products do not stop the remaining import process.
