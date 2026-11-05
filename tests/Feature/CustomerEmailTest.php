<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('officer_name');
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role');
            $table->string('status');
        });

        Schema::create('bank_statuses', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('is_open')->default(true);
        });
        DB::table('bank_statuses')->insert(['id' => 1, 'is_open' => true]);

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('status')->default('nonaktif');
            $table->string('account_number')->unique();
            $table->string('nis')->unique();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('class')->nullable();
            $table->text('location')->nullable();
            $table->string('telephone_number')->nullable();
            $table->string('pin');
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('qrcode')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('teller_id')->nullable();
            $table->string('transaction_type');
            $table->decimal('amount', 12, 2);
            $table->decimal('beginning_balance', 12, 2);
            $table->decimal('ending_balance', 12, 2);
            $table->dateTime('date');
        });
    }

    public function test_create_customer_form_has_required_email_input_for_otp_recovery(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('customers.create'))
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('type="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="pin"', false)
            ->assertSee('required', false)
            ->assertSee('kode OTP saat lupa password');
    }

    public function test_customer_email_is_required_when_creating_a_customer(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('customers.store'), [
                'account_number' => '10000001',
                'nis' => 'NIS-1',
                'name' => 'New Customer',
                'class' => 'XII',
                'location' => 'Jakarta',
                'telephone_number' => '081234567890',
                'password' => 'customer-login-password',
                'pin' => '123456',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_customer_email_can_be_updated_and_is_normalized(): void
    {
        $admin = $this->createAdmin();
        $customer = Customer::create([
            'account_number' => '10000001',
            'nis' => 'NIS-1',
            'name' => 'Existing Customer',
            'email' => 'old@example.test',
            'class' => 'XII',
            'location' => 'Jakarta',
            'telephone_number' => '081234567890',
            'pin' => bcrypt('123456'),
            'balance' => 10000,
        ]);

        $this->actingAs($admin)
            ->put(route('customers.update', $customer->id), [
                'nis' => 'NIS-1',
                'name' => 'Existing Customer',
                'email' => '  CUSTOMER@EXAMPLE.TEST ',
                'class' => 'XII',
                'location' => 'Jakarta',
                'telephone_number' => '081234567890',
            ])
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'email' => 'customer@example.test',
        ]);
    }

    public function test_new_customer_starts_inactive_with_zero_balance(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('customers.store'), [
                'account_number' => '10000001',
                'nis' => 'NIS-NEW',
                'name' => 'New Customer',
                'email' => 'new@example.test',
                'class' => 'XII',
                'location' => 'Jakarta',
                'telephone_number' => '081234567890',
                'password' => 'customer-login-password',
                'pin' => '123456',
            ])
            ->assertRedirect(route('customers.index'));

        $customer = Customer::where('account_number', '10000001')->firstOrFail();

        $this->assertEquals(0, $customer->balance);
        $this->assertSame('nonaktif', $customer->status);
        $this->assertTrue(password_verify(
            'customer-login-password',
            $customer->password
        ));
        $this->assertFalse(password_verify(
            '123456',
            $customer->password
        ));
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(password_verify('123456', $customer->pin));

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Customer Belum Aktif')
            ->assertSee('New Customer');

        if ($customer->qrcode) {
            File::delete(public_path('qrcode/' . $customer->qrcode));
        }
    }

    public function test_admin_cannot_activate_customer_with_less_than_minimum_balance(): void
    {
        $admin = $this->createAdmin();
        $customer = Customer::create([
            'account_number' => '10000002',
            'nis' => 'NIS-INACTIVE',
            'name' => 'Inactive Customer',
            'username' => 'inactivecustomer',
            'email' => 'inactive@example.test',
            'password' => 'password',
            'status' => 'nonaktif',
            'class' => 'XII',
            'location' => 'Jakarta',
            'telephone_number' => '081234567890',
            'pin' => bcrypt('123456'),
            'balance' => 0,
        ]);

        $this->actingAs($admin)
            ->patch(route('customers.status', $customer->id), [
                'status' => 'aktif',
            ])
            ->assertSessionHas('error', 'Customer hanya dapat diaktifkan jika saldo minimal Rp 10.000.');

        $this->assertSame('nonaktif', $customer->fresh()->status);

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Saldo harus minimal Rp 10.000')
            ->assertSee('disabled', false);
    }

    public function test_active_customer_logs_in_without_a_users_table_account(): void
    {
        $this->createAdmin();
        $customer = Customer::create([
            'account_number' => '10000005',
            'nis' => 'NIS-LOGIN',
            'name' => 'Login Customer',
            'email' => 'login@example.test',
            'username' => 'logincustomer',
            'password' => 'customer-password',
            'status' => 'aktif',
            'class' => 'XII',
            'location' => 'Jakarta',
            'telephone_number' => '081234567890',
            'pin' => bcrypt('123456'),
            'balance' => 10000,
        ]);

        $this->post(route('login.authenticate'), [
            'username' => $customer->username,
            'password' => 'customer-password',
        ])->assertRedirect(route('customer.dashboard'));

        $this->get(route('customer.dashboard'))->assertOk();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('customer.dashboard'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_can_activate_customer_with_minimum_balance(): void
    {
        $admin = $this->createAdmin();
        $customer = Customer::create([
            'account_number' => '10000003',
            'nis' => 'NIS-FUNDED',
            'name' => 'Funded Customer',
            'username' => 'fundedcustomer',
            'email' => 'funded@example.test',
            'password' => 'password',
            'status' => 'nonaktif',
            'class' => 'XII',
            'location' => 'Jakarta',
            'telephone_number' => '081234567890',
            'pin' => bcrypt('123456'),
            'balance' => 10000,
        ]);

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('name="status" value="aktif"', false)
            ->assertSee('Activate');

        $this->patch(route('customers.status', $customer->id), [
            'status' => 'aktif',
        ])->assertSessionHas('success', 'Status customer berhasil diperbarui.');

        $this->assertSame('aktif', $customer->fresh()->status);

        $response = $this->get(route('customers.index'))->assertOk();
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'Status customer berhasil diperbarui.')
        );
    }

    public function test_customer_accounts_are_not_managed_as_staff_users(): void
    {
        $admin = $this->createAdmin();
        Customer::create([
            'account_number' => '10000004',
            'nis' => 'NIS-INACTIVE-USER',
            'name' => 'Inactive Customer',
            'username' => 'inactiveusercustomer',
            'email' => 'inactive-user@example.test',
            'password' => 'password',
            'status' => 'nonaktif',
            'class' => 'XII',
            'location' => 'Jakarta',
            'telephone_number' => '081234567890',
            'pin' => bcrypt('123456'),
            'balance' => 9999,
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('Inactive Customer')
            ->assertSee('Admin');

        $this->post(route('users.store'), [
            'officer_name' => 'Should Not Be Customer',
            'username' => 'notacustomer',
            'password' => 'password',
            'role' => 'customer',
        ])->assertSessionHasErrors('role');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_new_user_is_created_as_inactive(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'officer_name' => 'New Teller',
                'username' => 'newteller',
                'email' => 'newteller@example.test',
                'password' => 'password',
                'role' => 'teller',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'newteller',
            'role' => 'teller',
            'status' => 'nonaktif',
        ]);
    }

    public function test_user_management_displays_and_searches_by_email(): void
    {
        $admin = $this->createAdmin();
        $admin->update(['email' => 'admin@example.test']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Email')
            ->assertSee('admin@example.test');

        $this->actingAs($admin)
            ->get(route('users.index', ['keyword' => 'admin@example.test']))
            ->assertOk()
            ->assertSee('admin@example.test');
    }

    public function test_user_management_displays_the_database_user_id(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('<th>ID</th>', false)
            ->assertSee('<td>' . $admin->id . '</td>', false);
    }

    private function createAdmin(): User
    {
        return User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);
    }
}
