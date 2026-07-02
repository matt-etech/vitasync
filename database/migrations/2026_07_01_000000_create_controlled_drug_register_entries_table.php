<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('controlled_drug_register_entries')) {
            Schema::create('controlled_drug_register_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('home_id')->constrained()->cascadeOnDelete();
                $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('witness_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('transaction_type');
                $table->dateTime('occurred_at');
                $table->string('drug_name');
                $table->string('form')->nullable();
                $table->string('strength')->nullable();
                $table->string('unit', 40);
                $table->string('stock_key', 191);
                $table->decimal('quantity', 10, 2);
                $table->decimal('signed_quantity', 10, 2);
                $table->decimal('expected_balance_before', 10, 2);
                $table->decimal('expected_balance_after', 10, 2);
                $table->decimal('actual_balance_after', 10, 2);
                $table->decimal('discrepancy_amount', 10, 2)->default(0);
                $table->text('discrepancy_reason')->nullable();
                $table->text('reason')->nullable();
                $table->string('source_or_destination')->nullable();
                $table->string('batch_number')->nullable();
                $table->date('expiry_date')->nullable();
                $table->boolean('witness_required')->default(false);
                $table->string('witness_name')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('submitted_at');
                $table->timestamps();

                $table->index(['home_id', 'stock_key'], 'cd_register_stock_key_index');
                $table->index(['home_id', 'occurred_at']);
                $table->index(['client_id', 'occurred_at']);
                $table->index('transaction_type');
                $table->index('discrepancy_amount');
            });
        } elseif (! Schema::hasColumn('controlled_drug_register_entries', 'stock_key')) {
            Schema::table('controlled_drug_register_entries', function (Blueprint $table) {
                $table->string('stock_key', 191)->default('')->after('unit');
            });

            DB::table('controlled_drug_register_entries')
                ->orderBy('id')
                ->select(['id', 'drug_name', 'strength', 'form', 'unit'])
                ->each(function (object $entry): void {
                    DB::table('controlled_drug_register_entries')
                        ->where('id', $entry->id)
                        ->update([
                            'stock_key' => self::stockKey(
                                (string) $entry->drug_name,
                                $entry->strength,
                                $entry->form,
                                (string) $entry->unit,
                            ),
                        ]);
                });

            Schema::table('controlled_drug_register_entries', function (Blueprint $table) {
                $table->index(['home_id', 'stock_key'], 'cd_register_stock_key_index');
                $table->index(['home_id', 'occurred_at']);
                $table->index(['client_id', 'occurred_at']);
                $table->index('transaction_type');
                $table->index('discrepancy_amount');
            });
        }

        $permissionId = DB::table('permissions')->where('name', 'controlled_drugs.manage')->value('id');

        if ($permissionId === null) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'controlled_drugs.manage',
                'description' => 'Manage controlled drug register entries, balances, witnesses, and discrepancies.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('roles')
            ->whereIn('name', ['Administrator', 'Home Manager'])
            ->pluck('id')
            ->each(function (int $roleId) use ($permissionId): void {
                DB::table('permission_role')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_drug_register_entries');

        $permissionId = DB::table('permissions')->where('name', 'controlled_drugs.manage')->value('id');

        if ($permissionId !== null) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permission_user')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }

    private static function stockKey(string $drugName, ?string $strength, ?string $form, string $unit): string
    {
        return str(implode('|', [
            mb_strtolower(trim($drugName)),
            mb_strtolower(trim((string) $strength)),
            mb_strtolower(trim((string) $form)),
            mb_strtolower(trim($unit)),
        ]))->limit(191, '')->toString();
    }
};
