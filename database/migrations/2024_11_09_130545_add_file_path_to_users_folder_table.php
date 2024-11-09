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
        Schema::table('users_folder', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('title'); // Add file_path after title
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_folder', function (Blueprint $table) {
            $table->dropColumn('file_path'); // Remove file_path on rollback
        });
    }
};