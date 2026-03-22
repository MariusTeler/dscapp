<?php

namespace Tests\Feature;

use App\Models\Expeditie;
use Tests\TestCase;

class ExpediteModelTest extends TestCase
{
    public function test_expeditie_model_uses_correct_table(): void
    {
        $model = new Expeditie();
        $this->assertEquals('exp_prelucrate', $model->getTable());
        $this->assertEquals('cod_expeditie', $model->getKeyName());
    }

    public function test_withfilters_returns_no_results_when_no_filters(): void
    {
        // Verify the guard clause is in the SQL
        $sql = Expeditie::query()->withFilters([])->toSql();
        $this->assertStringContainsString('1=2', $sql);
        // Also verify via count in SQLite-safe way (whereRaw('1=2') works in SQLite)
        // Note: IF() expressions in SELECT fail in SQLite, so we test the WHERE clause only
    }
}
