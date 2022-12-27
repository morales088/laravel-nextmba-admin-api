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
        Schema::create('video_libraries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('uid')->nullable();
            $table->string('speaker')->nullable();
            $table->string('logo')->nullable();
            $table->dateTime('date')->default(now());
            $table->string('color')->default('#F2A87B');
            $table->integer('broadcast_status')->default(0)->comment('[0 - unpublish, 1 - publish]');
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
        Schema::dropIfExists('video_libraries');
    }
};
