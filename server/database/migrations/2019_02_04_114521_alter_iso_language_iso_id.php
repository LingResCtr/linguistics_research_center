<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterIsoLanguageIsoId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('iso_language', function (Blueprint $table) {
            $table->renameColumn('id', 'iso_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('iso_language', function (Blueprint $table) {
            $table->renameColumn('iso_id', 'id');
        });
    }
}
