<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if(!Schema::hasTable('hourly_service_incidents') || Schema::hasColumn('hourly_service_incidents','unavailable_from'))return;
        Schema::table('hourly_service_incidents',function(Blueprint $t){$t->dateTime('unavailable_from')->nullable()->after('review_reason');});
    }
    public function down(): void
    {
        if(Schema::hasColumn('hourly_service_incidents','unavailable_from'))
            Schema::table('hourly_service_incidents',fn(Blueprint $t)=>$t->dropColumn('unavailable_from'));
    }
};
