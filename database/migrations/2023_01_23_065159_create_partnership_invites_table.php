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
        Schema::create('partnership_invites', function (Blueprint $table) {
            $table->id();
            $table->string('invitation_code');
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->integer('from_student_id')->comment('student owns invitation_code');
            $table->integer('status');
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
        Schema::dropIfExists('partnership_invites');
    }
};
