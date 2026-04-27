<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFnPrincipleSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fn_principle_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principle_id')->constrained('fn_principles')->onDelete('cascade');
            $table->double('principle_value')->default(0);
            $table->double('sales_value')->default(0);
            $table->double('total_price')->default(0);
            $table->date('date');
            $table->foreignId('tax_setting_id')->nullable()->constrained('fn_tax_settings')->onDelete('set null');
            $table->double('tax_setting_value')->default(0)->nullable();
            $table->double('total_tax_value')->default(0)->nullable();
            $table->foreignId('tax_account_id')->nullable()->constrained('fn_accounts')->onDelete('set null');
            $table->unsignedBigInteger('debit_account_id')->comment("المدين");
            $table->foreign('debit_account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->double('financial_value')->default(0);
            $table->string('notes', 1000)->nullable();
            $table->unsignedBigInteger('constraint_id')->nullable();
            $table->foreign('constraint_id')->references('id')->on('fn_constraints')->onDelete('set null');
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
        Schema::dropIfExists('fn_principle_sales');
    }
}
