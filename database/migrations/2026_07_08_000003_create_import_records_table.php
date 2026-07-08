<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('upload_id')
                  ->constrained('uploads')
                  ->cascadeOnDelete();

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();

            $table->unsignedInteger('row_number');

            $table->enum('action', ['create', 'update'])->nullable()
                  ->comment('create=new product, update=existing product patched; null until the worker determines the action');

            $table->enum('status', ['pending', 'processing', 'successful', 'failed'])
                  ->default('pending')
                  ->index();

            $table->json('shopify_response')->nullable()
                  ->comment('Raw Shopify GraphQL JSON response — includes userErrors on partial success');

            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            
            $table->index(['upload_id', 'row_number'], 'import_records_upload_row_index');
            $table->index(['product_id', 'status'], 'import_records_product_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_records');
    }
};
