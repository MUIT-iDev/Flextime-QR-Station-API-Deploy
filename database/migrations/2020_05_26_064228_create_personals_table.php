<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('personals')) { return; }

        Schema::create('personals', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->string('hriId', 18);
            $table->string('pid', 13);
            $table->string('name', 150);
            $table->string('surname', 150);
            $table->string('cardId', 30);
            $table->dateTime('modifyDate');

            $table->index('hriId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('personals');
    }
}
