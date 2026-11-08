<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WithdrawalAppointmentMigrationTest extends TestCase
{
    public function test_migration_creates_the_appointment_fields_and_foreign_keys(): void
    {
        Schema::dropIfExists('withdrawal_appointments');
        $migration = require database_path(
            'migrations/2026_11_08_000000_create_withdrawal_appointments_table.php'
        );

        $migration->up();

        $this->assertTrue(Schema::hasTable('withdrawal_appointments'));
        $this->assertTrue(Schema::hasColumns('withdrawal_appointments', [
            'customer_id',
            'withdrawal_date',
            'amount',
            'status',
            'reviewed_by',
            'reviewed_at',
            'rejection_reason',
            'consumed_at',
        ]));

        $foreignKeyColumns = collect(Schema::getForeignKeys('withdrawal_appointments'))
            ->pluck('columns')
            ->flatten()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['customer_id', 'reviewed_by'], $foreignKeyColumns);
    }
}
