<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MemberWorkflowTest extends TestCase
{
    private string $testPublicPath;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('officer_name');
            $table->string('username')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->string('role');
            $table->string('status');
        });

        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->string('nis')->unique();
            $table->string('name');
            $table->string('class');
            $table->text('location');
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
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
            $table->unsignedBigInteger('teller_id');
            $table->string('transaction_type');
            $table->decimal('amount', 12, 2);
            $table->decimal('beginning_balance', 12, 2);
            $table->decimal('ending_balance', 12, 2);
            $table->dateTime('date');
        });

        Schema::create('daily_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('teller_id');
            $table->date('date');
            $table->decimal('beginning_balance', 12, 2)->default(0);
            $table->decimal('total_deposit', 12, 2)->default(0);
            $table->decimal('total_withdrawals', 12, 2)->default(0);
            $table->decimal('ending_balance', 12, 2)->default(0);
            $table->decimal('physical_money', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            $table->string('status');
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
        });

        Schema::create('accounting_journal', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('account');
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->dateTime('date');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        $this->testPublicPath = storage_path('framework/testing/member-workflow-' . uniqid());
        File::ensureDirectoryExists($this->testPublicPath);
        app()->usePublicPath($this->testPublicPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testPublicPath);
        parent::tearDown();
    }

    public function test_admin_can_download_template_without_contact_or_bank_credentials(): void
    {
        $response = $this->actingAs($this->createAdmin())
            ->get(route('members.import.template'))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );

        $path = tempnam(sys_get_temp_dir(), 'member-template-');
        file_put_contents($path, $response->streamedContent());
        $spreadsheet = IOFactory::load($path);
        $this->assertSame(
            ['nis', 'name', 'class', 'location'],
            $spreadsheet->getSheetByName('Anggota')->rangeToArray('A1:D1')[0]
        );
        $this->assertSame('Panduan Import Anggota', $spreadsheet->getSheetByName('Petunjuk')->getCell('A1')->getValue());

        $spreadsheet->disconnectWorksheets();
        unlink($path);
    }

    public function test_import_saves_only_prospective_members_without_creating_bank_accounts(): void
    {
        $this->actingAs($this->createAdmin())
            ->post(route('members.import'), [
                'file' => $this->makeImportFile([
                    ['NIS-1', 'New Member', 'XII', 'Jakarta'],
                    ['NIS-2', 'Second Member', 'XI', 'Bandung'],
                ]),
            ])
            ->assertRedirect(route('members.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('members', 2);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('members', [
            'nis' => 'NIS-1',
            'name' => 'New Member',
            'class' => 'XII',
            'location' => 'Jakarta',
        ]);
        $this->assertDirectoryDoesNotExist(public_path('qrcode'));
    }

    public function test_admin_can_add_member_manually(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('members.create'))
            ->assertOk()
            ->assertSee('Add Anggota');

        $this->post(route('members.store'), [
            'nis' => 'NIS-MANUAL',
            'name' => 'Manual Member',
            'class' => 'XII',
            'location' => 'Jakarta',
        ])
            ->assertRedirect(route('members.index'))
            ->assertSessionHas('success', 'Anggota berhasil ditambahkan.');

        $this->assertDatabaseHas('members', [
            'nis' => 'NIS-MANUAL',
            'name' => 'Manual Member',
            'class' => 'XII',
            'location' => 'Jakarta',
        ]);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_member_cannot_be_added_with_a_nis_already_used_by_a_customer(): void
    {
        $admin = $this->createAdmin();
        Customer::create([
            'account_number' => '10000001',
            'nis' => 'NIS-EXISTING',
            'name' => 'Existing Customer',
            'username' => 'existingcustomer',
            'email' => 'existing@example.test',
            'password' => 'password',
            'status' => 'nonaktif',
            'class' => 'XII',
            'location' => 'Jakarta',
            'telephone_number' => '081234567890',
            'pin' => Hash::make('012345'),
            'balance' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('members.store'), [
                'nis' => 'NIS-EXISTING',
                'name' => 'Duplicate Member',
                'class' => 'XII',
                'location' => 'Jakarta',
            ])
            ->assertSessionHasErrors('nis');

        $this->assertDatabaseCount('members', 0);
    }

    public function test_duplicate_member_import_is_rejected_without_partial_saves(): void
    {
        $this->actingAs($this->createAdmin())
            ->post(route('members.import'), [
                'file' => $this->makeImportFile([
                    ['NIS-DUP', 'First Member', 'XII', 'Jakarta'],
                    ['NIS-DUP', 'Second Member', 'XII', 'Jakarta'],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('import_errors');

        $this->assertDatabaseCount('members', 0);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDirectoryDoesNotExist(public_path('qrcode'));
    }

    public function test_import_rejects_old_contact_and_credential_columns(): void
    {
        $this->actingAs($this->createAdmin())
            ->post(route('members.import'), [
                'file' => $this->makeImportFile(
                    [['NIS-OLD', 'Old Format Member', 'old@example.test', 'XII', 'Jakarta', '081234567890', 'password', '012345']],
                    ['nis', 'name', 'email', 'class', 'location', 'telephone_number', 'password', 'pin']
                ),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Format Excel tidak sesuai. Unduh dan gunakan template import anggota.');

        $this->assertDatabaseCount('members', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_teller_opens_account_with_minimum_deposit_and_member_record_is_retained(): void
    {
        $teller = $this->createTeller();
        $admin = $this->createAdmin();
        $member = Member::create([
            'nis' => 'NIS-ACCOUNT',
            'name' => 'New Bank Customer',
            'class' => 'XII',
            'location' => 'Jakarta',
        ]);

        $this->actingAs($teller)
            ->get(route('teller.members.open.form', $member->id))
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="telephone_number"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="pin"', false)
            ->assertSee('name="opening_deposit"', false)
            ->assertSee('value="10.000"', false)
            ->assertSee('type="text"', false);

        $this->post(route('teller.members.open', $member->id), [
            'email' => 'newcustomer@example.test',
            'telephone_number' => '081234567890',
            'password' => 'customer-password',
            'pin' => '012345',
            'opening_deposit' => '15.000',
        ])
            ->assertRedirect(route('transactions.receipt', 1));

        $customer = Customer::where('nis', 'NIS-ACCOUNT')->firstOrFail();
        $this->assertSame('10000001', $customer->account_number);
        $this->assertSame('New Bank Customer', $customer->name);
        $this->assertSame('newcustomer@example.test', $customer->email);
        $this->assertSame('081234567890', $customer->telephone_number);
        $this->assertEquals(15000, $customer->balance);
        $this->assertSame('aktif', $customer->status);
        $this->assertSame('new bank customer', $customer->username);
        $this->assertTrue(Hash::check('customer-password', $customer->password));
        $this->assertTrue(Hash::check('012345', $customer->pin));
        $this->assertDatabaseHas('members', ['id' => $member->id, 'nis' => 'NIS-ACCOUNT']);
        $this->actingAs($admin)
            ->get(route('members.index'))
            ->assertOk()
            ->assertSee('NIS-ACCOUNT')
            ->assertSee('10000001')
            ->assertSee('Aktif')
            ->assertDontSee('<th>Saldo</th>', false)
            ->assertDontSee('15.000');
        $this->assertDatabaseHas('transactions', [
            'customer_id' => $customer->id,
            'teller_id' => $teller->id,
            'transaction_type' => 'setoran',
            'amount' => 15000,
            'beginning_balance' => 0,
            'ending_balance' => 15000,
        ]);
        $this->assertDatabaseHas('daily_reports', [
            'teller_id' => $teller->id,
            'total_deposit' => 15000,
            'ending_balance' => 15000,
        ]);
        $this->assertDatabaseCount('accounting_journal', 2);
        $this->assertFileExists(public_path('qrcode/' . $customer->qrcode));
    }

    public function test_teller_must_enter_minimum_opening_deposit(): void
    {
        $teller = $this->createTeller();
        $member = Member::create([
            'nis' => 'NIS-LOW-DEPOSIT',
            'name' => 'Low Deposit Member',
            'class' => 'XII',
            'location' => 'Jakarta',
        ]);

        $this->actingAs($teller)
            ->post(route('teller.members.open', $member->id), [
                'email' => 'lowdeposit@example.test',
                'telephone_number' => '081234567890',
                'password' => 'customer-password',
                'pin' => '012345',
                'opening_deposit' => 9999,
            ])
            ->assertSessionHasErrors('opening_deposit');

        $this->assertDatabaseCount('members', 1);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_teller_cannot_open_an_account_from_a_member_twice(): void
    {
        $teller = $this->createTeller();
        $member = Member::create([
            'nis' => 'NIS-ALREADY-OPEN',
            'name' => 'Already Open',
            'class' => 'XII',
            'location' => 'Jakarta',
        ]);
        Customer::create([
            'account_number' => '10000001',
            'nis' => $member->nis,
            'name' => $member->name,
            'username' => 'alreadyopen',
            'email' => 'alreadyopen@example.test',
            'password' => 'password',
            'status' => 'aktif',
            'class' => $member->class,
            'location' => $member->location,
            'telephone_number' => '081234567890',
            'pin' => Hash::make('012345'),
            'balance' => 10000,
        ]);

        $this->actingAs($teller)
            ->get(route('teller.members.open.form', $member->id))
            ->assertRedirect(route('teller.members.index'))
            ->assertSessionHas('error', 'Anggota ini sudah memiliki rekening.');

        $this->assertDatabaseCount('members', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_member_management_is_available_only_to_admins_and_tellers_cannot_access_admin_list(): void
    {
        $teller = User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'email' => 'teller@example.test',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);

        $this->actingAs($teller)
            ->get(route('members.index'))
            ->assertForbidden();

        $this->get(route('teller.members.index'))
            ->assertOk();
    }

    public function test_teller_member_table_is_visible_and_search_filters_members(): void
    {
        $teller = $this->createTeller();
        Member::create([
            'nis' => 'NIS-SEARCH-1',
            'name' => 'Alpha Member',
            'class' => 'XII',
            'location' => 'Jakarta',
        ]);
        Member::create([
            'nis' => 'NIS-SEARCH-2',
            'name' => 'Beta Member',
            'class' => 'XI',
            'location' => 'Bandung',
        ]);

        $this->actingAs($teller)
            ->get(route('teller.members.index'))
            ->assertOk()
            ->assertSee('<table', false)
            ->assertSee('NIS-SEARCH-1')
            ->assertSee('NIS-SEARCH-2')
            ->assertSee('name="keyword"', false)
            ->assertSee(route('teller.members.index'), false);

        $this->get(route('teller.members.index', ['keyword' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Member')
            ->assertDontSee('Beta Member');
    }

    public function test_admin_can_promote_students_and_keep_selected_students_in_their_class(): void
    {
        $admin = $this->createAdmin();
        $x = Member::create([
            'nis' => 'NIS-X',
            'name' => 'Student X',
            'class' => 'X RPL 1',
            'location' => 'Jakarta',
        ]);
        $xi = Member::create([
            'nis' => 'NIS-XI',
            'name' => 'Student XI',
            'class' => 'XI',
            'location' => 'Jakarta',
        ]);
        $xPromoted = Member::create([
            'nis' => 'NIS-X-PROMOTED',
            'name' => 'Promoted Student X',
            'class' => 'X AKL 2',
            'location' => 'Jakarta',
        ]);
        $xii = Member::create([
            'nis' => 'NIS-XII',
            'name' => 'Student XII',
            'class' => 'XII',
            'location' => 'Jakarta',
        ]);
        $alumni = Member::create([
            'nis' => 'NIS-ALUMNI',
            'name' => 'Existing Alumni',
            'class' => 'ALUMNI',
            'location' => 'Jakarta',
        ]);

        $alumniCustomer = Customer::create([
            'account_number' => '10000001',
            'nis' => $xii->nis,
            'name' => 'Student XII',
            'username' => 'studentxii',
            'email' => 'studentxii@example.test',
            'password' => 'password',
            'status' => 'aktif',
            'class' => 'XII',
            'location' => 'Jakarta',
            'telephone_number' => '081234567890',
            'pin' => Hash::make('012345'),
            'balance' => 10000,
        ]);
        $alumniCustomer->createToken('student-mobile');

        $this->actingAs($admin)
            ->get(route('members.promotion'))
            ->assertOk()
            ->assertSee('Student X')
            ->assertSee('X RPL 1')
            ->assertSee('ALUMNI')
            ->assertDontSee('Existing Alumni');

        $this->post(route('members.promote'), [
            'stay_ids' => [$x->id],
        ])
            ->assertRedirect(route('members.promotion'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('members', ['id' => $x->id, 'class' => 'X RPL 1']);
        $this->assertDatabaseHas('members', ['id' => $xPromoted->id, 'class' => 'XI AKL 2']);
        $this->assertDatabaseHas('members', ['id' => $xi->id, 'class' => 'XII']);
        $this->assertDatabaseHas('members', ['id' => $xii->id, 'class' => 'ALUMNI']);
        $this->assertDatabaseHas('members', ['id' => $alumni->id, 'class' => 'ALUMNI']);
        $this->assertDatabaseHas('customers', ['nis' => $xii->nis, 'class' => 'ALUMNI']);
        $this->assertDatabaseHas('customers', ['id' => $alumniCustomer->id, 'status' => 'nonaktif']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function createAdmin(): User
    {
        return User::create([
            'officer_name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'aktif',
        ]);
    }

    private function createTeller(): User
    {
        return User::create([
            'officer_name' => 'Teller',
            'username' => 'teller',
            'email' => 'teller@example.test',
            'password' => 'password',
            'role' => 'teller',
            'status' => 'aktif',
        ]);
    }

    private function makeImportFile(
        array $rows,
        array $headers = ['nis', 'name', 'class', 'location']
    ): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Anggota');
        $sheet->fromArray([
            $headers,
            ...$rows,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'member-import-');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $content = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('members.xlsx', $content);
    }
}
