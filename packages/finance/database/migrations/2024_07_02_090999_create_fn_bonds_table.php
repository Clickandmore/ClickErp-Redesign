<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFnBondsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fn_bonds', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("code");
            $table->string("type",255);// enum class
            $table->tinyInteger("payment_type");// enum class
            $table->string("bond_book_num",255)->default("-")->comment("الرقم الدفترى");
            $table->unsignedBigInteger('debit_account_id')->comment("المدين");
            $table->foreign('debit_account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->unsignedBigInteger('credit_account_id')->comment("الدائن");
            $table->foreign('credit_account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->double("value")->default(0);
            $table->date("date");
            $table->string("statement",500)->default("-");
            $table->string("file",500)->default("no file");
            $table->string("transfer_number",500)->default("nothing")->comment("رقم التحويل ");
            $table->string("transfer_file",500)->default("nothing")->comment("صورة التحويل");
            $table->boolean("is_confirm")->default(false)->comment("حالة الترحيل ");
            $table->unsignedBigInteger('constraint_id')->nullable();
            $table->foreign('constraint_id')->references('id')->on('fn_constraints')->onDelete('cascade');
            $table->unsignedBigInteger('created_by_id');
            $table->foreign('created_by_id')->references('id')->on('admins')->onDelete('cascade');
            $table->unsignedBigInteger('year_id')->nullable();
            $table->foreign('year_id')->references('id')->on('fn_years')->onDelete('cascade');
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->foreign('cost_center_id')->references('id')->on('fn_cost_centers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fn_bonds');
    }
}
