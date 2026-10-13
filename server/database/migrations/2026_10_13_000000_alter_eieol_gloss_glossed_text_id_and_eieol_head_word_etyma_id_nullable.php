<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * eieol_gloss.glossed_text_id and eieol_head_word.etyma_id have always been
 * nullable in production: the lesson editor detaches a gloss from its glossed
 * text by setting glossed_text_id to NULL, and saves a head word without an
 * etymon as etyma_id NULL. Replayed on Laravel 11 or later, the ->change()
 * calls in 2023_10_07_085121_fix_table_id_types_to_bigint drop that
 * nullability, because change() no longer keeps modifiers it is not given.
 * This restores the production definition; where the columns are already
 * nullable it changes nothing.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('eieol_gloss', function (Blueprint $table) {
            $table->unsignedBigInteger('glossed_text_id')->nullable(true)->change();
        });

        Schema::table('eieol_head_word', function (Blueprint $table) {
            $table->unsignedBigInteger('etyma_id')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Deliberately empty: NOT NULL was never the intended schema, and existing
     * detached glosses and head words without an etymon would make it fail.
     */
    public function down(): void
    {
        //
    }
};
