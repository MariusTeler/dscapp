<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_dsc_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'hashParola'));
        $this->assertTrue(Schema::hasColumn('users', 'nivel_acces'));
        $this->assertTrue(Schema::hasColumn('users', 'centru_id'));
        $this->assertTrue(Schema::hasColumn('users', 'activ'));
        $this->assertTrue(Schema::hasColumn('users', 'user'));
    }

    public function test_sessions_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('sessions'));
    }
}
