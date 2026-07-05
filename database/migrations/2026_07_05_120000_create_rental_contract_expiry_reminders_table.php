<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rental_contract_expiry_reminders')) {
            $this->addMissingIndexes();

            return;
        }

        Schema::create('rental_contract_expiry_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rental_contract_id');
            $table->string('token', 80)->unique();
            $table->string('channel', 30)->default('sms');
            $table->string('status', 40)->default('pending');
            $table->string('response', 40)->nullable();
            $table->date('expires_on');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->boolean('sms_sent')->default(false);
            $table->boolean('whatsapp_sent')->default(false);
            $table->text('send_error')->nullable();
            $table->timestamps();

            $table->foreign('rental_contract_id')
                ->references('id')
                ->on('rental_contracts')
                ->onDelete('cascade');

            $table->index(['rental_contract_id', 'expires_on'], 'rental_exp_rem_contract_expires_idx');
            $table->index(['status', 'expires_on'], 'rental_exp_rem_status_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_contract_expiry_reminders');
    }

    private function addMissingIndexes(): void
    {
        if (!$this->indexExists('rental_exp_rem_contract_expires_idx')) {
            DB::statement('ALTER TABLE `rental_contract_expiry_reminders` ADD INDEX `rental_exp_rem_contract_expires_idx` (`rental_contract_id`, `expires_on`)');
        }

        if (!$this->indexExists('rental_exp_rem_status_expires_idx')) {
            DB::statement('ALTER TABLE `rental_contract_expiry_reminders` ADD INDEX `rental_exp_rem_status_expires_idx` (`status`, `expires_on`)');
        }
    }

    private function indexExists(string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'rental_contract_expiry_reminders')
            ->where('index_name', $indexName)
            ->exists();
    }
};
