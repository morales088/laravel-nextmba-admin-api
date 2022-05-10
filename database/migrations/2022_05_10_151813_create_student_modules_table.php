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
        Schema::create('student_modules', function (Blueprint $table) {
            $table->id();
            $table->integer('studentId');
            $table->integer('moduleId');
            $table->string('remarks')->nullable();
            $table->integer('status')->default(1)->comment('[0 - delete, 1 - active, 2 - pending, 3 - completed]');
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
        Schema::dropIfExists('student_modules');
    }
};
