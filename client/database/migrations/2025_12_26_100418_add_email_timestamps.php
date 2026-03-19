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
        if (Schema::hasTable('client_expeditii')) {
            Schema::table('client_expeditii', function (Blueprint $table) {
                if (!Schema::hasColumn('client_expeditii', 'destinatar_email')) {
                    $table->string('destinatar_email')->nullable();
                }
                if (!Schema::hasColumn('client_expeditii', 'created_at')) {
                    $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
                }
            });
        }
        if (Schema::hasTable('client_destinatari')) {
            Schema::table('client_destinatari', function (Blueprint $table) {
                if (!Schema::hasColumn('client_destinatari', 'email')) {
                    $table->string('email')->nullable();
                }
            });
        }
        if (Schema::hasTable('exp_prelucrate')) {
            Schema::table('exp_prelucrate', function (Blueprint $table) {
                if (!Schema::hasColumn('exp_prelucrate', 'expeditor_email')) {
                    $table->string('expeditor_email')->nullable();
                }
                if (!Schema::hasColumn('exp_prelucrate', 'destinatar_email')) {
                    $table->string('destinatar_email')->nullable();
                }
            });
        }
        if (Schema::hasTable('clienti')) {
            Schema::table('clienti', function (Blueprint $table) {
                if (!Schema::hasColumn('clienti', 'email')) {
                    $table->string('email')->nullable();
                }
            });
        }
        if (Schema::hasTable('comenzi')) {
            Schema::table('comenzi', function (Blueprint $table) {
                if (!Schema::hasColumn('comenzi', 'email')) {
                    $table->string('email')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_expeditii', function (Blueprint $table) {
            $table->dropColumn('destinatar_email');
        });
        Schema::table('client_destinatari', function (Blueprint $table) {
            $table->dropColumn('email');
        });
        Schema::table('exp_prelucrate', function (Blueprint $table) {
            $table->dropColumn('expeditor_email');
            $table->dropColumn('destinatar_email');
        });
        Schema::table('clienti', function (Blueprint $table) {
            $table->dropColumn('email');
        });
        Schema::table('comenzi', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
