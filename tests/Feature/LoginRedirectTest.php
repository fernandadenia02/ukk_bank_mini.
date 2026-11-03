<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('officer_name');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role');
            $table->string('status');
        });
    }

    public function test_authenticated_teller_is_redirected_from_login_to_their_dashboard(): void
    {
        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);

        $this->actingAs($teller)
            ->get(route('login'))
            ->assertRedirect('/');

        $this->get('/')
            ->assertRedirect(route('teller.dashboard'));
    }
}
