<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_folder_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('users_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('users_folder_id')->nullable()->constrained('users_folder')->onDelete('cascade');
            $table->foreignId('users_folder_shareable_id')->nullable()->constrained('users_folder_shareable')->onDelete('cascade'); // New nullable foreign key
            $table->foreignId('users_subfolder_shareable_id')->nullable()->constrained('user_subfolder_shareable')->onDelete('cascade'); // New nullable foreign key
            $table->unsignedBigInteger('subfolder_id')->nullable();

            $table->foreign('subfolder_id')->references('id')->on('subfolders')->onDelete('cascade'); // Cascade on subfolder delete
            $table->string('files');
            $table->integer('size')->default(0);
            $table->string('extension');

            $table->enum('protected', ['YES', 'NO'])->default('NO');
            $table->string('password')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->index(['users_id', 'extension']);
            $table->index(['users_folder_id', 'size']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_folder_files');
    }
};
