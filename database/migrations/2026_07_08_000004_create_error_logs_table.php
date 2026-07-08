<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     */
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();

            // Both FKs are nullable — errors may occur before a product row
            // exists, or outside the scope of any specific upload.
            $table->foreignId('upload_id')
                  ->nullable()
                  ->constrained('uploads')
                  ->nullOnDelete();

            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained('products')
                  ->nullOnDelete();

            // PSR-3 severity levels.
            $table->enum('level', ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'])
                  ->default('error')
                  ->index()
                  ->comment('PSR-3 log level');

            // Which class/service produced the error.
            $table->string('source')->nullable();

            $table->text('message')->comment('Human-readable error description');

            // Full PHP exception including stack trace.
            $table->longText('exception')->nullable();

            // Arbitrary structured context for debugging.
            $table->json('payload')->nullable();

            $table->timestamps();
            $table->index(['upload_id', 'level'], 'error_logs_upload_level_index');
            $table->index(['product_id', 'level'], 'error_logs_product_level_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
