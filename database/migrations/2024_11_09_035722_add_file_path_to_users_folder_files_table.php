<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()

{

    Schema::table('users_folder_files', function (Blueprint $table) {

        $table->string('file_path')->nullable(); // or use appropriate type

    });

}


public function down()

{

    Schema::table('users_folder_files', function (Blueprint $table) {

        $table->dropColumn('file_path');

    });

}
};
