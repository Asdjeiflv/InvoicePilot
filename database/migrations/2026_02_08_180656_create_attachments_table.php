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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');  // attachable_type, attachable_id (auto-indexed)
            $table->string('file_name')->comment('File name');
            $table->string('file_path')->comment('File path');
            $table->string('mime_type')->nullable()->comment('MIME type');
            $table->unsignedBigInteger('size')->default(0)->comment('File size in bytes');
            $table->foreignId('uploaded_by')->constrained('users')->comment('Uploaded by user ID');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
