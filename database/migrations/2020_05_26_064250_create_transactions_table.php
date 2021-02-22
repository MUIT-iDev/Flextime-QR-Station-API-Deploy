<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('transactions')) { return; }

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->string('cardId', 30);
            $table->dateTime('scanTime');
            $table->string('scanDetail', 1000);
            $table->string('hriId', 50)->nullable();
            $table->string('latitude', 30)->nullable();
            $table->string('longtitude', 30)->nullable();
            $table->string('expireDate', 10)->nullable();
            $table->string('expireTime', 10)->nullable();
            $table->Integer('timeDiffSec')->nullable();
            $table->string('qrType', 30)->nullable();
            $table->string('scanStatus', 20);
            $table->boolean('sendStatus');
            $table->dateTime('sendDate')->nullable();

            $table->index('sendStatus');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
