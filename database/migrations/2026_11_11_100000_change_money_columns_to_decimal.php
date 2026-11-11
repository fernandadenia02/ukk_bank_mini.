<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MONEY_COLUMNS = [
        'customers' => ['balance'],
        'transactions' => ['amount', 'beginning_balance', 'ending_balance'],
        'daily_reports' => [
            'beginning_balance',
            'total_deposit',
            'total_withdrawals',
            'ending_balance',
            'physical_money',
            'difference',
        ],
        'accounting_journal' => ['debit', 'credit'],
        'accounting_journals' => ['debit', 'credit'],
    ];

    public function up(): void
    {
        $this->changeMoneyColumnsTo('decimal');
    }

    public function down(): void
    {
        foreach (self::MONEY_COLUMNS as $tableName => $columnNames) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            foreach ($columnNames as $columnName) {
                if (! Schema::hasColumn($tableName, $columnName)) {
                    continue;
                }

                DB::table($tableName)
                    ->select($columnName)
                    ->whereNotNull($columnName)
                    ->orderBy('id')
                    ->chunk(500, function ($rows) use ($tableName, $columnName): void {
                        foreach ($rows as $row) {
                            $value = (string) $row->{$columnName};

                            if (! preg_match('/^-?\d+(?:\.0+)?$/', $value)) {
                                throw new \RuntimeException(
                                    "Cannot convert {$tableName}.{$columnName} to an integer without losing decimal values."
                                );
                            }
                        }
                    });
            }
        }

        $this->changeMoneyColumnsTo('bigInteger');
    }

    private function changeMoneyColumnsTo(string $type): void
    {
        foreach (self::MONEY_COLUMNS as $tableName => $columnNames) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $columns = collect(Schema::getColumns($tableName))
                ->keyBy('name');
            $expectedTypes = $type === 'decimal'
                ? ['decimal', 'numeric']
                : ['bigint', 'integer'];
            $columnsToChange = array_values(array_filter(
                $columnNames,
                fn (string $columnName): bool => $columns->has($columnName)
                    && ! in_array(Schema::getColumnType($tableName, $columnName), $expectedTypes, true)
            ));

            if ($columnsToChange === []) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columnsToChange, $columns, $type): void {
                foreach ($columnsToChange as $columnName) {
                    $definition = $type === 'decimal'
                        ? $table->decimal($columnName, 12, 2)
                        : $table->bigInteger($columnName);
                    $metadata = $columns->get($columnName);

                    if ($metadata['nullable']) {
                        $definition->nullable();
                    }

                    if ($metadata['default'] !== null) {
                        $default = trim((string) $metadata['default'], "'\"");

                        if (! is_numeric($default)) {
                            throw new \RuntimeException(
                                "Cannot preserve the non-numeric default for {$columnName}."
                            );
                        }

                        $definition->default($default);
                    }

                    $definition->change();
                }
            });
        }
    }
};
