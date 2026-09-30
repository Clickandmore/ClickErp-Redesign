<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UseCurrentShelterFormData extends Migration
{
    public function up()
    {
        Schema::table('shelter_documents', function (Blueprint $table) {
            $table->timestamp('release_completed_at')->nullable();
            $table->string('revision', 36)->nullable();
        });
        // Retain only the latest entered values, not independent document copies.
        if (Schema::hasTable('shelter_document_movements')) {
            DB::table('shelter_document_movements')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    if ($row->kind !== 'release') continue;
                    $values = ['data' => $row->data, 'release_completed_at' => $row->created_at];
                    DB::table('shelter_documents')->where('contract_id', $row->contract_id)->where('kind', $row->kind)->update($values);
                }
            });
            Schema::drop('shelter_document_movements');
        }
        Schema::table('shelter_documents', fn (Blueprint $table) => $table->dropColumn('template_snapshot'));
    }

    public function down()
    {
        Schema::table('shelter_documents', function (Blueprint $table) {
            $table->json('template_snapshot')->nullable();
            $table->dropColumn(['release_completed_at', 'revision']);
        });
        Schema::create('shelter_document_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->index();
            $table->string('kind', 20);
            $table->string('action', 80);
            $table->unsignedBigInteger('template_id');
            $table->json('template_snapshot');
            $table->json('data');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }
}
