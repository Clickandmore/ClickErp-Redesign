<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientPollAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_client_poll_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('crm_clients')->onDelete('cascade');
            $table->foreignId('poll_id')->constrained('crm_polls')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('crm_questions')->onDelete('cascade');
            $table->foreignId('answer_id')->nullable()->constrained('crm_questions_answers')->onDelete('cascade')->comment('فاضية لو السوال مقالي');
            $table->text('essay_answer')->nullable();
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
        Schema::dropIfExists('crm_client_poll_answers');
    }
}
