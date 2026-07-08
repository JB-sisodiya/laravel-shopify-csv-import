<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();

            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');

            $table->unsignedInteger('total_rows')->default(0)
                  ->comment('Total data rows parsed from the CSV (header excluded)');
            $table->unsignedInteger('processed_rows')->default(0)
                  ->comment('Rows that have been attempted (success + failure)');
            $table->unsignedInteger('successful_rows')->default(0)
                  ->comment('Rows that were successfully pushed to Shopify');
            $table->unsignedInteger('failed_rows')->default(0)
                  ->comment('Rows that failed after all retry attempts');

            // Lifecycle status of the entire upload batch.
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending')
                  ->index()
                  ->comment('pending=queued, processing=workers active, completed=all rows done, failed=unrecoverable error');

            $table->timestamp('started_at')->nullable()
                  ->comment('Set when the first queue worker picks up this upload');
            $table->timestamp('completed_at')->nullable()
                  ->comment('Set when all rows have been processed or the upload is marked failed');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
