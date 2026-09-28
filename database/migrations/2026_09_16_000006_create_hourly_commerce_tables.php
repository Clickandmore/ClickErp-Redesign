<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('hourly_packages')) Schema::create('hourly_packages', function (Blueprint $t) {
            $t->id(); $t->string('code', 40)->unique(); $t->string('category', 100);
            $t->decimal('hours_per_visit', 5, 2); $t->unsignedInteger('workers_per_visit')->default(1);
            $t->unsignedInteger('visits_count'); $t->unsignedInteger('validity_days');
            foreach (['price_per_visit', 'extra_worker_price', 'extra_hour_price', 'transport_fee'] as $c) $t->decimal($c, 12, 2)->default(0);
            $t->decimal('tax_rate', 5, 2)->default(15); $t->boolean('transport_included')->default(true);
            foreach (['min_notice_hours', 'cancellation_window_hours', 'cancellation_fee_percent'] as $c) $t->unsignedInteger($c)->nullable();
            $t->string('expiry_policy')->default('forfeit'); $t->boolean('is_active')->default(false);
            $t->unsignedInteger('sort_order')->default(0); $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps(); $t->softDeletes();
        });
        if (!Schema::hasTable('hourly_package_translations')) Schema::create('hourly_package_translations', function (Blueprint $t) {
            $t->id(); $t->foreignId('hourly_package_id')->constrained('hourly_packages')->cascadeOnDelete();
            $t->string('locale', 5); $t->string('title'); $t->text('description')->nullable();
            $t->unique(['hourly_package_id', 'locale']); $t->timestamps();
        });
        foreach (['city', 'nationality'] as $type) {
            if (!Schema::hasTable('hourly_package_'.$type)) Schema::create('hourly_package_'.$type, function (Blueprint $t) use ($type) {
                $t->foreignId('hourly_package_id')->constrained('hourly_packages')->cascadeOnDelete();
                $t->unsignedBigInteger($type.'_id'); $t->unique(['hourly_package_id', $type.'_id'], 'hp_'.$type.'_unique');
            });
        }
        foreach (['city', 'nationality'] as $type) {
            $table = 'hourly_package_'.$type;
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);
            $hasUnique = collect($indexes)->contains(fn ($index) => $index->isUnique() && $index->getColumns() === ['hourly_package_id', $type.'_id']);
            if (!$hasUnique) Schema::table($table, fn (Blueprint $t) => $t->unique(['hourly_package_id', $type.'_id'], 'hp_'.$type.'_unique'));
            $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys($table);
            $hasForeign = collect($foreignKeys)->contains(fn ($key) => $key->getLocalColumns() === ['hourly_package_id']);
            if (!$hasForeign) Schema::table($table, fn (Blueprint $t) => $t->foreign('hourly_package_id', 'hp_'.$type.'_fk')->references('id')->on('hourly_packages')->cascadeOnDelete());
        }
        if (!Schema::hasTable('hourly_rental_orders')) Schema::create('hourly_rental_orders', function (Blueprint $t) {
            $t->id(); $t->string('order_number')->unique(); $t->string('invoice_number')->unique();
            $t->unsignedBigInteger('user_id')->index(); $t->unsignedBigInteger('city_id')->index();
            $t->foreignId('hourly_package_id')->constrained('hourly_packages')->restrictOnDelete();
            $t->string('source', 20); $t->unsignedBigInteger('created_by_admin_id')->nullable();
            $t->json('package_snapshot'); $t->text('address_text');
            $t->decimal('latitude',10,7); $t->decimal('longitude',10,7);
            foreach (['district', 'building', 'unit', 'contact_phone'] as $c) $t->string($c)->nullable();
            $t->decimal('hours_per_visit',5,2); $t->unsignedInteger('workers_per_visit'); $t->unsignedInteger('visits_total');
            foreach (['price_per_visit','extra_worker_price','extra_hour_price','transport_fee','subtotal','discount_amount','tax_amount','total_amount','paid_amount'] as $c) $t->decimal($c,12,2)->default(0);
            $t->decimal('tax_rate',5,2); $t->json('schedule_pattern')->nullable();
            $t->dateTime('starts_at'); $t->dateTime('expires_at')->index();
            $t->string('status',30)->index(); $t->string('payment_method',30); $t->string('payment_status',20)->default('unpaid');
            $t->text('notes')->nullable(); $t->timestamps();
        });
        if (!Schema::hasTable('hourly_visits')) Schema::create('hourly_visits', function (Blueprint $t) {
            $t->id(); $t->foreignId('hourly_rental_order_id')->constrained('hourly_rental_orders')->restrictOnDelete();
            $t->unsignedInteger('visit_number'); $t->unsignedBigInteger('hourly_team_id')->nullable();
            $t->date('scheduled_date')->nullable(); $t->dateTime('scheduled_start_at')->nullable(); $t->dateTime('scheduled_end_at')->nullable();
            $t->decimal('planned_hours',5,2); $t->unsignedInteger('workers_count'); $t->decimal('visit_price',12,2);
            $t->text('address_text'); $t->decimal('latitude',10,7); $t->decimal('longitude',10,7); $t->unsignedBigInteger('city_id');
            foreach (['district','building','unit','contact_phone'] as $c) $t->string($c)->nullable();
            $t->string('status',30)->default('unscheduled')->index(); $t->decimal('cancellation_fee',12,2)->default(0);
            $t->timestamps(); $t->unique(['hourly_rental_order_id','visit_number']);
        });
        if (!Schema::hasTable('hourly_order_payments')) Schema::create('hourly_order_payments', function (Blueprint $t) {
            $t->id(); $t->foreignId('hourly_rental_order_id')->constrained('hourly_rental_orders')->restrictOnDelete();
            $t->unsignedBigInteger('user_id'); $t->uuid('public_id')->unique();
            $t->string('method',30); $t->string('provider')->nullable(); $t->string('provider_reference')->nullable()->unique();
            $t->json('provider_payload')->nullable(); $t->text('payment_url')->nullable();
            $t->decimal('amount',12,2); $t->string('status',20); $t->dateTime('paid_at')->nullable();
            $t->string('collected_by_type',20)->nullable(); $t->unsignedBigInteger('collected_by_id')->nullable();
            $t->string('attachment_path')->nullable(); $t->timestamps();
        });
        if (!Schema::hasTable('hourly_order_status_logs')) Schema::create('hourly_order_status_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('hourly_rental_order_id')->constrained('hourly_rental_orders')->restrictOnDelete();
            $t->string('from_status')->nullable(); $t->string('to_status'); $t->string('actor_type');
            $t->unsignedBigInteger('actor_id')->nullable(); $t->text('reason')->nullable(); $t->timestamps();
        });
        if (Schema::hasTable('user_addresses')) foreach (['building','unit','contact_phone'] as $c) {
            if (!Schema::hasColumn('user_addresses',$c)) Schema::table('user_addresses', fn (Blueprint $t) => $t->string($c)->nullable());
        }
    }
    public function down()
    {
        foreach (['hourly_order_status_logs','hourly_order_payments','hourly_visits','hourly_rental_orders','hourly_package_nationality','hourly_package_city','hourly_package_translations','hourly_packages'] as $table) Schema::dropIfExists($table);
        // Address columns are additive shared data; retain them on rollback.
    }
};
