<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSubfolderShareableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_subfolder_shareable', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The owner who shares the subfolder
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade'); // The recipient with whom the subfolder is shared
            $table->foreignId('subfolder_id')->constrained('subfolders')->onDelete('cascade'); // Foreign key to the subfolder
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_subfolder_shareable');
    }
}
