<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BankStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('officer_name');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role');
            $table->string('status');
        });

        Schema::create('bank_statuses', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('is_open')->default(true);
        });

        DB::table('bank_statuses')->insert([
            'id' => 1,
            'is_open' => true,
        ]);
    }

    public function test_teller_can_switch_bank_between_open_and_break(): void
    {
        $teller = $this->createUser('teller');

        $this->actingAs($teller)
            ->post(route('teller.bank-status.update'), ['is_open' => '0'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Bank sedang istirahat dan ditutup sementara.');

        $this->assertDatabaseHas('bank_statuses', [
            'id' => 1,
            'is_open' => false,
        ]);

        $this->actingAs($teller)
            ->post(route('teller.bank-status.update'), ['is_open' => '1'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Status bank berhasil diubah menjadi buka.');

        $this->assertDatabaseHas('bank_statuses', [
            'id' => 1,
            'is_open' => true,
        ]);
    }

    public function test_only_tellers_can_change_bank_status(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->post(route('teller.bank-status.update'), ['is_open' => '0'])
            ->assertForbidden();

        $this->assertDatabaseHas('bank_statuses', [
            'id' => 1,
            'is_open' => true,
        ]);
    }

    public function test_bank_status_indicator_shows_red_and_green_states(): void
    {
        $this->assertStringContainsString(
            'Bank Buka',
            view('shared.bank-status', ['bankIsOpen' => true, 'showControls' => false])->render()
        );
        $this->assertStringContainsString(
            '#198754',
            view('shared.bank-status', ['bankIsOpen' => true, 'showControls' => false])->render()
        );

        $this->assertStringContainsString(
            'Bank Tutup / Istirahat',
            view('shared.bank-status', ['bankIsOpen' => false, 'showControls' => false])->render()
        );
        $this->assertStringContainsString(
            '#dc3545',
            view('shared.bank-status', ['bankIsOpen' => false, 'showControls' => false])->render()
        );
    }

    public function test_teller_controls_show_clear_close_and_open_actions(): void
    {
        $openView = view('shared.bank-status', [
            'bankIsOpen' => true,
            'showControls' => true,
        ])->render();
        $closedView = view('shared.bank-status', [
            'bankIsOpen' => false,
            'showControls' => true,
        ])->render();

        $this->assertStringContainsString('Tutup Bank / Istirahat', $openView);
        $this->assertStringContainsString('action="' . route('teller.bank-status.update') . '"', $openView);
        $this->assertStringContainsString('name="is_open" value="0"', $openView);
        $this->assertStringContainsString('Buka Bank', $closedView);
        $this->assertStringContainsString('name="is_open" value="1"', $closedView);
    }

    private function createUser(string $role): User
    {
        return User::create([
            'officer_name' => ucfirst($role),
            'username' => $role,
            'password' => 'password',
            'role' => $role,
            'status' => 'aktif',
        ]);
    }
}
