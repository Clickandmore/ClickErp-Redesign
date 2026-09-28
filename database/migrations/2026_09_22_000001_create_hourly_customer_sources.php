<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hourly_customer_sources',function(Blueprint $t){$t->string('code',100)->primary();$t->string('name');});
        $known=['admin'=>'الإدارة','app'=>'التطبيق','web'=>'الموقع','company_visa'=>'تأشيرة الشركة'];
        foreach($known as $code=>$name)DB::table('hourly_customer_sources')->insert(['code'=>$code,'name'=>$name]);
        DB::table('users')->whereNotNull('register_from')->where('register_from','!=','')->select('register_from')->distinct()->orderBy('register_from')->chunk(100,function($rows){foreach($rows as $row)DB::table('hourly_customer_sources')->insertOrIgnore(['code'=>$row->register_from,'name'=>$row->register_from]);});
    }
    public function down(): void { Schema::dropIfExists('hourly_customer_sources'); }
};
