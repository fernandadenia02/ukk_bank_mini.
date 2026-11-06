<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WithdrawalPinApprovalMigrationTest extends TestCase
{
    public function test_migration_is_idempotent_when_table_already_exists(): void
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

        $migration->up();

        $this->assertTrue(Schema::hasColumns('withdrawal_pin_approvals', [
            'customer_id',
            'teller_id',
            'status',
        ]));
    }
}
