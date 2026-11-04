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

    public function test_guest_can_view_login_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('BANK MINI SEKOLAH')
            ->assertSee('Username Petugas')
            ->assertSee('Untuk Nasabah Siswa')
            ->assertSee('Akses layanan tabungan secara mandiri melalui aplikasi nasabah.')
            ->assertDontSee('Flutter')
            ->assertSee('Masuk ke Sistem')
            ->assertSee('https://bank-mini-fernanda.web.id/downloads/app-release.apk', false)
            ->assertSee('Logo SMK Negeri 1 Bantul')
            ->assertSee('/images/logo-smkn-1-bantul.png', false)
            ->assertSee('/css/login.css', false)
            ->assertSee('/css/bank-mini-ui.css', false);

        $this->assertFileExists(public_path('images/logo-smkn-1-bantul.png'));
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
