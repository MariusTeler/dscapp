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
        // DSC users table - shared with app2 / cliapi
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
                $table->integer('expeditor_id')->nullable()->index();
                $table->tinyInteger('print_awb')->default(0);
                $table->tinyInteger('activ')->default(1);
                $table->tinyInteger('selectie_puncte_de_lucru')->default(0);
                $table->tinyInteger('preturi')->default(0);
                $table->tinyInteger('recantarite')->default(0);
                $table->tinyInteger('importcsv')->default(0);
                $table->tinyInteger('show_master_clienti')->default(0);
                $table->tinyInteger('def_sms')->default(0);
                $table->string('def_obsv')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
