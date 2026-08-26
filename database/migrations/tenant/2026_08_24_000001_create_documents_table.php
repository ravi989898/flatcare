<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Society document library — tenant database. Files are stored on the
     * public disk the same way users.profile_photo_path already is (see
     * UserResource), just with a small metadata row per file rather than a
     * bare path column, since a document also needs a title, a category to
     * sort it into the resident app's Documents tabs, and a size/mime pair
     * to render in the list without touching the filesystem.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->enum('category', ['society', 'maintenance_bill', 'notice', 'legal', 'bylaw'])->default('society');

            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();

            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
