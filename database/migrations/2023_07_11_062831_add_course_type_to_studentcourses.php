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
            $table->integer('course_type')->default(1)->comment('[1 - paid, 2 - manually added, 3 - gifted]')->after('courseId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('studentcourses', function (Blueprint $table) {
            $table->dropColumn('course_type');
        });
    }
};
