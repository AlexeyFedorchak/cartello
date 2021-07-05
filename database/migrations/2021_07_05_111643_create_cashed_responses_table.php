<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashedResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cashed_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chart_id');
            $table->longText('response');
            $table->timestamps();

            $table->foreign('chart_id')
                ->on('charts')
                ->references('id')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cashed_responses');
    }
}
