<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSearchAnalytics2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('search_analytics_2', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('domain_id')->index()->nullable();
            $table->string('domain');
            $table->string('date');
            $table->longText('query');
            $table->string('device');
            $table->string('country');
            $table->string('clicks')->float();
            $table->string('impressions')->float();
            $table->string('position')->float();
            $table->timestamps();

            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
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
        Schema::dropIfExists('search_analytics_2');
    }
}
