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
        Schema::create('speaker_roles', function (Blueprint $table) {
            $table->id();
            $table->integer('topicId');
            // $table->integer('speakerId');
            $table->integer('role')->default(1)->comment('[1 - main speaker, 2 - guest speaker]');
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
        Schema::dropIfExists('speakers_topic');
    }
};
