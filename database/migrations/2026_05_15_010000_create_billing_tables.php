<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('admission_date')->nullable();
            $table->string('room_bed')->nullable();
            $table->string('billing_contact_name');
            $table->string('billing_contact_relationship')->nullable();
            $table->string('billing_contact_email')->nullable();
            $table->string('billing_contact_phone')->nullable();
            $table->string('funding_source');
            $table->string('payment_terms')->default('Due on receipt');
            $table->char('currency', 3)->default('USD');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('tax_exempt')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['funding_source', 'status']);
        });

        Schema::create('billing_rate_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->decimal('room_fee', 12, 2)->default(0);
            $table->decimal('care_fee', 12, 2)->default(0);
            $table->boolean('meals_included')->default(true);
            $table->string('billing_cycle')->default('monthly');
            $table->unsignedTinyInteger('due_day')->default(1);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('notice_period_days')->default(30);
            $table->string('late_fee_type')->default('none');
            $table->decimal('late_fee_amount', 12, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['status', 'billing_cycle']);
        });

        Schema::create('billing_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_profile_id')->constrained('billing_profiles')->cascadeOnDelete();
            $table->foreignId('billing_rate_plan_id')->constrained('billing_rate_plans')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->unsignedTinyInteger('due_day')->default(1);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->unsignedSmallInteger('notice_period_days')->default(30);
            $table->string('late_fee_type')->default('none');
            $table->decimal('late_fee_amount', 12, 2)->default(0);
            $table->json('care_level_pricing')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['billing_profile_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_profile_id')->constrained('billing_profiles')->cascadeOnDelete();
            $table->foreignId('billing_contract_id')->nullable()->constrained('billing_contracts')->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('issue_date');
            $table->date('due_date');
            $table->char('currency', 3)->default('USD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('credit_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['billing_profile_id', 'status']);
            $table->index(['due_date', 'status']);
        });

        Schema::create('billing_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_profile_id')->constrained('billing_profiles')->cascadeOnDelete();
            $table->foreignId('billing_contract_id')->nullable()->constrained('billing_contracts')->nullOnDelete();
            $table->foreignId('billing_invoice_id')->nullable()->constrained('billing_invoices')->nullOnDelete();
            $table->foreignId('staff_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('charge_type');
            $table->string('category');
            $table->string('description');
            $table->date('charge_date');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_credit')->default(false);
            $table->string('approval_status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->date('generated_for_period_start')->nullable();
            $table->date('generated_for_period_end')->nullable();
            $table->timestamps();

            $table->index(['billing_profile_id', 'approval_status']);
            $table->index(['billing_invoice_id', 'charge_type']);
        });

        Schema::create('billing_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->foreignId('billing_charge_id')->nullable()->constrained('billing_charges')->nullOnDelete();
            $table->string('item_type');
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_amount', 12, 2);
            $table->decimal('line_subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_profile_id')->constrained('billing_profiles')->cascadeOnDelete();
            $table->foreignId('billing_invoice_id')->constrained('billing_invoices')->cascadeOnDelete();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_number')->unique();
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['billing_profile_id', 'payment_date']);
        });

        Schema::create('billing_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_payment_id')->unique()->constrained('billing_payments')->cascadeOnDelete();
            $table->string('receipt_number')->unique();
            $table->timestamp('issued_at');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('payer_name')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_statement_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_profile_id')->constrained('billing_profiles')->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('entry_type');
            $table->string('description');
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->decimal('running_balance', 12, 2)->default(0);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();

            $table->index(['billing_profile_id', 'entry_date']);
            $table->index(['source_type', 'source_id']);
        });

        $permissionId = DB::table('permissions')->where('name', 'billing.manage')->value('id');

        if ($permissionId === null) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'billing.manage',
                'description' => 'Manage resident billing profiles, charges, invoices, payments, receipts, and statements.',
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
                ], []);
            });
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'billing.manage')->value('id');

        if ($permissionId !== null) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('billing_statement_entries');
        Schema::dropIfExists('billing_receipts');
        Schema::dropIfExists('billing_payments');
        Schema::dropIfExists('billing_invoice_items');
        Schema::dropIfExists('billing_charges');
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('billing_contracts');
        Schema::dropIfExists('billing_rate_plans');
        Schema::dropIfExists('billing_profiles');
    }
};
