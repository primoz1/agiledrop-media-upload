<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();


            /*
             |--------------------------------------------------------------------------
             | Ownership / Audit
             |--------------------------------------------------------------------------
             */
            $table->foreignId('client_id')
                  ->nullable()
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('uploaded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            /*
             |--------------------------------------------------------------------------
             | Metadata
             |--------------------------------------------------------------------------
             */
            $table->string('title');
            $table->text('description')->nullable();

            /*
             |--------------------------------------------------------------------------
             | Media files
             |--------------------------------------------------------------------------
             */

            // Media type is set after upload processing.
            // The upload endpoint does not determine the type synchronously.
            $table->enum('type', ['image', 'video'])->nullable();

            // Path to the original uploaded file
            $table->string('original_path')->nullable();

            // Path to the generated thumbnail (max 200x200)
            $table->string('thumbnail_path')->nullable();

            /*
             |--------------------------------------------------------------------------
             | Processing state
             |--------------------------------------------------------------------------
             */
            $table->enum('status', ['queued', 'processing', 'ready', 'failed'])
                  ->default('queued');

            // Error details in case async processing fails
            $table->text('error_message')->nullable();

            /*
             |--------------------------------------------------------------------------
             | Processing queries optimization
             |--------------------------------------------------------------------------
             |
             | This composite index is used to efficiently query media records
             | by client and processing status (e.g., listing all pending jobs
             | or processing failures for a specific client).
             |
            */
            $table->index(['client_id', 'status']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
