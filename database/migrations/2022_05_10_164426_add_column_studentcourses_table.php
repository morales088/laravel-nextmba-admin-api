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
        Schema::table('studentcourses', function (Blueprint $table) {
            $table->dateTime('expirationDate')->default(now()->addMonths(1));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('studentcourses', function (Blueprint $table) {
            $table->dropColumn('expirationDate');
        });
    }
};
