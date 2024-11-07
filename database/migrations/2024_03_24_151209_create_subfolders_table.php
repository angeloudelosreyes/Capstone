<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubfoldersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('subfolders', function (Blueprint $table) {
            $table->id(); // This will be an unsignedBigInteger
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_folder_id')->constrained('users_folder')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('subfolders');
    }
}
