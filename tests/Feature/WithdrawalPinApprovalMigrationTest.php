<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WithdrawalPinApprovalMigrationTest extends TestCase
{
    public function test_migration_creates_table_with_customer_and_teller_foreign_keys(): void
    {
        Schema::dropIfExists('withdrawal_pin_approvals');
        $migration = require database_path(
            'migrations/2026_11_06_100000_create_withdrawal_pin_approvals_table.php'
        );

        $migration->up();

        $this->assertTrue(Schema::hasTable('withdrawal_pin_approvals'));
        $this->assertTrue(Schema::hasColumns('withdrawal_pin_approvals', [
            'customer_id',
            'teller_id',
            'amount',
            'status',
            'expires_at',
            'approved_at',
            'consumed_at',
        ]));

        $foreignKeys = Schema::getForeignKeys('withdrawal_pin_approvals');
        $foreignKeyColumns = collect($foreignKeys)
            ->pluck('columns')
            ->flatten()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['customer_id', 'teller_id'], $foreignKeyColumns);
    }
}
