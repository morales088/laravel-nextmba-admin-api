<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->boolean('is_live')->default(false)->after('description');
            $table->time('end_time')->default(now()->addHours(4))->after('description');
            $table->time('starting_time')->default(now())->after('description');
            $table->date('date')->default(now())->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->dropColumn('is_live');
            $table->dropColumn('end_time');
            $table->dropColumn('starting_time');
            $table->dropColumn('date');
        });
    }
};
