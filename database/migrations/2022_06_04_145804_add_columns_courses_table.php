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
        Schema::table('courses', function (Blueprint $table) {;
            $table->string('course_link')->after('description');
            $table->string('telegram_link')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->dropColumn('course_link');
            $table->dropColumn('telegram_link');
        });
    }
};
