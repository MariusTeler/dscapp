<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stub migration for exp_prelucrate and related tables needed by the test environment.
 * In production these tables are managed externally (legacy DSC DB).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exp_prelucrate', function (Blueprint $table) {
            $table->bigInteger('cod_expeditie')->primary();
            $table->bigInteger('expeditie')->default(0);
            $table->date('data_expeditie')->nullable();
            $table->string('tip_exp', 10)->nullable();
            $table->string('tip_obj', 10)->nullable();
            $table->unsignedBigInteger('expeditor_id')->nullable();
            $table->unsignedBigInteger('destinatar_id')->nullable();
            $table->unsignedBigInteger('curier_preluare_id')->nullable();
            $table->unsignedBigInteger('curier_livrare_id')->nullable();
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->unsignedBigInteger('idfact')->nullable();
            $table->integer('piese')->default(0);
            $table->integer('plicuri')->default(0);
            $table->integer('colete')->default(0);
            $table->integer('paleti')->default(0);
            $table->decimal('greutate', 10, 2)->default(0);
            $table->decimal('km_preluare', 10, 2)->default(0);
            $table->decimal('km_livrare', 10, 2)->default(0);
            $table->decimal('valoare_asigurata', 10, 2)->default(0);
            $table->decimal('ramburs', 10, 2)->default(0);
            $table->decimal('valoare_totala_expeditie', 10, 2)->default(0);
            $table->string('tip_plata', 20)->nullable();
            $table->string('mod_plata', 20)->nullable();
            $table->tinyInteger('anulata')->default(0);
            $table->tinyInteger('liv_samb')->default(0);
            $table->tinyInteger('liv_sed')->default(0);
            $table->text('observatii')->nullable();
        });

        Schema::create('agenti', function (Blueprint $table) {
            $table->bigInteger('cod_ag')->primary();
            $table->string('nume_ag', 100)->nullable();
        });

        Schema::create('clienti', function (Blueprint $table) {
            $table->bigInteger('cod_cl')->primary();
            $table->string('nume', 150)->nullable();
            $table->unsignedBigInteger('zona_id')->default(0);
            $table->unsignedBigInteger('cod_lc')->nullable();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('centru_id')->nullable();
        });

        Schema::create('centre', function (Blueprint $table) {
            $table->id();
            $table->string('nume', 100)->nullable();
        });

        Schema::create('localitati', function (Blueprint $table) {
            $table->bigInteger('cod_lc')->primary();
            $table->string('nume_lc', 100)->nullable();
            $table->unsignedBigInteger('cod_centru')->nullable();
        });

        Schema::create('exp_facturi', function (Blueprint $table) {
            $table->id();
            $table->string('invoice', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exp_facturi');
        Schema::dropIfExists('localitati');
        Schema::dropIfExists('centre');
        Schema::dropIfExists('zones');
        Schema::dropIfExists('clienti');
        Schema::dropIfExists('agenti');
        Schema::dropIfExists('exp_prelucrate');
    }
};
