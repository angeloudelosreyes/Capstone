<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('age')->nullable(); // Make nullable if not required
            $table->string('address')->nullable(); // Make nullable if not required
            $table->string('department')->nullable(); // Make nullable if not required
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->enum('roles', ['USER', 'ADMIN'])->default('USER');
            $table->timestamps(); // This will automatically handle created_at and updated_at
            $table->index(['email', 'roles']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
