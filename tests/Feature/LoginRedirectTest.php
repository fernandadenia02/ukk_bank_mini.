<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
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

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('password')->nullable();
            $table->string('status')->default('aktif');
            $table->string('name')->nullable();
        });
    }

    public function test_guest_can_view_login_form(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertSee('BANK MINI SEKOLAH')
            ->assertSee('Username Petugas')
            ->assertSee('Untuk Nasabah Siswa')
            ->assertSee('Akses layanan tabungan secara mandiri melalui aplikasi nasabah.')
            ->assertDontSee('Flutter')
            ->assertSee('Masuk ke Sistem')
            ->assertSee('data-password-toggle', false)
            ->assertSee('aria-label="Lihat kata sandi"', false)
            ->assertSee('https://bank-mini-fernanda.web.id/downloads/app-release.apk', false)
            ->assertSee('Logo SMK Negeri 1 Bantul')
            ->assertSee('/images/logo-smkn-1-bantul.png', false)
            ->assertSee('/css/login.css', false)
            ->assertSee('/css/bank-mini-ui.css', false);

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, '<body'), strpos($html, '/css/login.css'));
        $this->assertFileExists(public_path('images/logo-smkn-1-bantul.png'));
    }

    public function test_password_login_is_locked_after_ten_failures_and_unlocked_after_thirty_minutes(): void
    {
        $user = User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => Hash::make('correct-password'),
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post(route('login.authenticate'), [
                'username' => $user->username,
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login.authenticate'), [
            'username' => $user->username,
            'password' => 'correct-password',
        ])
            ->assertRedirect('/')
            ->assertSessionHas('error', 'Terlalu banyak percobaan kata sandi salah. Coba lagi dalam 30 menit.');

        $this->travel(30)->minutes();

        $this->post(route('login.authenticate'), [
            'username' => $user->username,
            'password' => 'correct-password',
        ])->assertRedirect(route('admin.dashboard'));
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

    public function test_session_timeout_is_five_minutes_and_layout_submits_idle_logout(): void
    {
        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);

        $this->assertSame(5, config('session.lifetime'));

        $this->actingAs($teller)
            ->view('layouts.app')
            ->assertSee('idleTimeout = 5 * 60 * 1000', false)
            ->assertSee("timeoutField.name = 'idle_timeout'", false)
            ->assertSee("logoutForm.requestSubmit()", false);
    }

    public function test_idle_logout_redirects_to_login_with_expiration_message(): void
    {
        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);

        $this->actingAs($teller)
            ->post(route('logout'), ['idle_timeout' => '1'])
            ->assertRedirect(route('login'))
            ->assertSessionHas(
                'error',
                'Sesi berakhir karena tidak ada aktivitas selama 5 menit. Silakan login kembali.'
            );
    }
}
