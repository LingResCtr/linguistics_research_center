<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issue_comment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('issue_id');
            $table->string('type', 16);
            $table->longText('text');
            $table->string('user_logon', 64);
            $table->timestamps();

            $table->foreign('issue_id')->references('id')->on('issue');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_comment');
    }
}
