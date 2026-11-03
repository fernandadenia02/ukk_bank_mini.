<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            if (!Schema::hasColumn('customers', 'username')) {
                $table->string('username', 50)->nullable();
            }
            if (!Schema::hasColumn('customers', 'password')) {
                $table->string('password')->nullable();
            }
            if (!Schema::hasColumn('customers', 'status')) {
                $table->string('status', 20)->default('nonaktif');
            }
        });

        if (Schema::hasTable('users')) {
            DB::table('users')
                ->whereIn('role', ['customer', 'nasabah'])
                ->orderBy('id')
                ->get()
                ->each(function (object $account): void {
                    $customerQuery = DB::table('customers');
                    if (Schema::hasColumn('customers', 'user_id')) {
                        $customerQuery->where('user_id', $account->id);
                    } elseif (Schema::hasColumn('customers', 'email') && !empty($account->email)) {
                        $customerQuery->where('email', $account->email);
                    } else {
                        return;
                    }

                    $customer = $customerQuery->first();
                    if (!$customer && Schema::hasColumn('customers', 'email') && !empty($account->email)) {
                        $customer = DB::table('customers')
                            ->where('email', $account->email)
                            ->first();
                    }
                    if (!$customer) {
                        return;
                    }

                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update([
                            'username' => $account->username,
                            'password' => $account->password,
                            'status' => $account->status,
                        ]);

                    if (Schema::hasTable('personal_access_tokens')) {
                        DB::table('personal_access_tokens')
                            ->where('tokenable_type', App\Models\User::class)
                            ->where('tokenable_id', $account->id)
                            ->update([
                                'tokenable_type' => App\Models\Customer::class,
                                'tokenable_id' => $customer->id,
                            ]);
                    }
                });
        }

        DB::table('customers')
            ->whereNull('username')
            ->orWhere('username', '')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $customer): void {
                $baseUsername = 'customer-legacy-' . $customer->id;
                $username = $baseUsername;
                $suffix = 1;

                while (DB::table('customers')->where('username', $username)->exists()) {
                    $username = $baseUsername . '-' . $suffix;
                    $suffix++;
                }

                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update(['username' => $username]);
            });

        if (!Schema::hasIndex('customers', 'customers_username_unique')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->unique('username');
            });
        }

        if (Schema::hasTable('users')) {
            $customerUserIds = DB::table('users')
                ->whereIn('role', ['customer', 'nasabah'])
                ->pluck('id');

            if ($customerUserIds->isNotEmpty()) {
                if (Schema::hasTable('personal_access_tokens')) {
                    DB::table('personal_access_tokens')
                        ->where('tokenable_type', App\Models\User::class)
                        ->whereIn('tokenable_id', $customerUserIds)
                        ->delete();
                }

                if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                    DB::table('sessions')->whereIn('user_id', $customerUserIds)->delete();
                }

            }

            if (Schema::hasColumn('customers', 'user_id')) {
                foreach (Schema::getForeignKeys('customers') as $foreignKey) {
                    if (in_array('user_id', $foreignKey['columns'], true)) {
                        Schema::table('customers', function (Blueprint $table) use ($foreignKey): void {
                            $table->dropForeign($foreignKey['name'] ?? ['user_id']);
                        });
                    }
                }

                Schema::table('customers', function (Blueprint $table): void {
                    $table->dropColumn('user_id');
                });
            }

            if ($customerUserIds->isNotEmpty()) {
                DB::table('users')->whereIn('id', $customerUserIds)->delete();
            }
        }
    }

    public function down(): void
    {
        // Credentials are moved and customer user rows are removed; do not discard those accounts on rollback.
    }
};
