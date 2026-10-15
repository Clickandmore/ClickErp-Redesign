<?php

use App\Support\RentalExtensionMessage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('sms_templates')->insertOrIgnore([
            'name' => RentalExtensionMessage::TEMPLATE_NAME,
            'title' => 'رسالة تمديد عقد التأجير',
            'channel' => 'sms',
            'sort_order' => 100,
            'message' => RentalExtensionMessage::defaultMessage(),
            'variables' => json_encode(RentalExtensionMessage::VARIABLES),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Retain the template because its text may have been customized by an administrator.
    }
};
