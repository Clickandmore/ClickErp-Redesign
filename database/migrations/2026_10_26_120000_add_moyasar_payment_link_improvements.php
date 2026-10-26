<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تحسينات ميسر: نص الفاتورة، قناة إرسال الرابط، ودفع نقل الخدمات عبر ميسر.
 * كل الأعمدة اختيارية بقيم افتراضية تبقي السلوك الحالي كما هو.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('moyasar_settings')) {
            Schema::table('moyasar_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('moyasar_settings', 'company_name')) $table->string('company_name')->nullable();
                if (!Schema::hasColumn('moyasar_settings', 'payment_description')) $table->string('payment_description', 500)->nullable();
            });
        }

        if (Schema::hasTable('rental_contract_infos') && !Schema::hasColumn('rental_contract_infos', 'payment_link_sent_channel')) {
            Schema::table('rental_contract_infos', fn (Blueprint $table) => $table->string('payment_link_sent_channel', 20)->nullable());
        }

        if (Schema::hasTable('sponsorship_transfers')) {
            Schema::table('sponsorship_transfers', function (Blueprint $table) {
                $columns = [
                    'moyasar_invoice_id' => fn () => $table->string('moyasar_invoice_id')->nullable()->unique(),
                    'moyasar_payment_id' => fn () => $table->string('moyasar_payment_id')->nullable()->index(),
                    'moyasar_payment_url' => fn () => $table->string('moyasar_payment_url', 2048)->nullable(),
                    'moyasar_payment_status' => fn () => $table->string('moyasar_payment_status', 30)->nullable(),
                    'moyasar_amount' => fn () => $table->decimal('moyasar_amount', 12, 2)->nullable(),
                    'moyasar_refunded_amount' => fn () => $table->decimal('moyasar_refunded_amount', 12, 2)->default(0),
                    'moyasar_callback_token' => fn () => $table->string('moyasar_callback_token', 64)->nullable()->unique(),
                    'moyasar_paid_at' => fn () => $table->timestamp('moyasar_paid_at')->nullable(),
                    'moyasar_receipt_bond_id' => fn () => $table->unsignedBigInteger('moyasar_receipt_bond_id')->nullable(),
                    'payment_link_sent_at' => fn () => $table->timestamp('payment_link_sent_at')->nullable(),
                    'payment_link_sent_channel' => fn () => $table->string('payment_link_sent_channel', 20)->nullable(),
                ];
                foreach ($columns as $column => $add) {
                    if (!Schema::hasColumn('sponsorship_transfers', $column)) $add();
                }
            });
        }

        // كل استرداد جزئي من ميسر يُسجَّل بسند صرف مستقل، والمعلم التراكمي يمنع تكراره.
        if (!Schema::hasTable('sponsorship_transfer_moyasar_refunds')) {
            Schema::create('sponsorship_transfer_moyasar_refunds', function (Blueprint $table) {
                $table->id();
                // الاسم الافتراضي للفهرس يتجاوز حد MySQL (64 حرفاً).
                $table->unsignedBigInteger('sponsorship_transfer_id')->index('transfer_moyasar_refunds_transfer_idx');
                $table->decimal('amount', 12, 2);
                $table->decimal('cumulative_amount', 12, 2);
                $table->unsignedBigInteger('bond_id');
                $table->timestamps();
                $table->unique(['sponsorship_transfer_id', 'cumulative_amount'], 'transfer_moyasar_refund_milestone_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsorship_transfer_moyasar_refunds');

        if (Schema::hasTable('sponsorship_transfers')) {
            $columns = array_values(array_filter([
                'moyasar_invoice_id', 'moyasar_payment_id', 'moyasar_payment_url', 'moyasar_payment_status',
                'moyasar_amount', 'moyasar_refunded_amount', 'moyasar_callback_token', 'moyasar_paid_at',
                'moyasar_receipt_bond_id', 'payment_link_sent_at', 'payment_link_sent_channel',
            ], fn ($column) => Schema::hasColumn('sponsorship_transfers', $column)));
            if ($columns) {
                Schema::table('sponsorship_transfers', function (Blueprint $table) use ($columns) {
                    foreach (['moyasar_invoice_id', 'moyasar_callback_token'] as $unique) {
                        if (in_array($unique, $columns, true)) $table->dropUnique([$unique]);
                    }
                    if (in_array('moyasar_payment_id', $columns, true)) $table->dropIndex(['moyasar_payment_id']);
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasColumn('rental_contract_infos', 'payment_link_sent_channel')) {
            Schema::table('rental_contract_infos', fn (Blueprint $table) => $table->dropColumn('payment_link_sent_channel'));
        }

        if (Schema::hasTable('moyasar_settings')) {
            $columns = array_values(array_filter(['company_name', 'payment_description'],
                fn ($column) => Schema::hasColumn('moyasar_settings', $column)));
            if ($columns) Schema::table('moyasar_settings', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
