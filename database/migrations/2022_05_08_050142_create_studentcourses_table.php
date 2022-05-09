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
        Schema::create('studentcourses', function (Blueprint $table) {
            $table->id();
            $table->integer('studentId');
            $table->integer('courseId');
            $table->integer('status')->default(1)->comment('[0 - deleted, 1 - active]');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('studentcourses');
    }
};
