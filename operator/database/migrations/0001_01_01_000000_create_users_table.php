<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('user')->unique();
                $table->string('hashParola')->nullable();
                $table->string('email')->nullable()->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('nume')->nullable();
                $table->string('telefon')->nullable();
                $table->tinyInteger('nivel_acces')->default(0);
                $table->integer('centru_id')->nullable()->default(0);
                $table->integer('expeditor_id')->nullable()->index();
                $table->tinyInteger('activ')->default(1);
                $table->tinyInteger('preturi')->default(0);
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            if (!Schema::hasColumn('users', 'centru_id')) {
                Schema::table('users', fn (Blueprint $t) => $t->integer('centru_id')->nullable()->default(0));
            }
            if (!Schema::hasColumn('users', 'hashParola')) {
                Schema::table('users', fn (Blueprint $t) => $t->string('hashParola')->nullable());
            }
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
