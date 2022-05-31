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
        Schema::create('gifter_courses', function (Blueprint $table) {
            $table->id();
            $table->integer('from_student_id');
            $table->integer('course_id');
            $table->string('unique_id');
            $table->string('email');
            $table->dateTime('date')->default(now());
            $table->integer('status')->default(1)->comment('[1 - pending, 2 - canceled, 3 - active]');
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
        Schema::dropIfExists('gifter_courses');
    }
};
