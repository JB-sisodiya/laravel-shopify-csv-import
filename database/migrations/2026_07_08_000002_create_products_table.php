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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('upload_id')
                  ->constrained('uploads')
                  ->cascadeOnDelete();

            $table->string('shopify_product_id')->nullable()->index();

            // Core Shopify product fields.
            $table->string('handle')->index();
            $table->string('title');
            $table->longText('body_html')->nullable();
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();

            $table->json('tags')->nullable();

            // Variant / inventory fields.
            $table->string('sku')->nullable()->index();
            $table->string('barcode')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->integer('inventory_quantity')->default(0);

            // Shipping weight — nullable because not all products require shipping.
            $table->decimal('weight', 10, 3)->nullable();
            $table->enum('weight_unit', ['g', 'kg', 'lb', 'oz'])->nullable();

            // Media.
            $table->string('image_url')->nullable();

            // Per-row processing status.
            $table->enum('status', ['pending', 'processing', 'successful', 'failed'])
                  ->default('pending')
                  ->index()
                  ->comment('pending=waiting in queue, processing=worker active, successful=pushed to Shopify, failed=all retries exhausted');

            $table->timestamps();
            // Composite index for upload + handle — enables fast duplicate detection
            // within the same batch before hitting the Shopify API.
            $table->index(['upload_id', 'handle'], 'products_upload_handle_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
