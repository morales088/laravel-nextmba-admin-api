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
        //->nullable()
        if (Schema::hasColumn('topics', 'start_time')) {
            Schema::table('topics', function (Blueprint $table) {
                $table->time('start_time')->nullable()->change();
                $table->time('end_time')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
