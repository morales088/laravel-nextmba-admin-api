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
        Schema::create('replay_videos', function (Blueprint $table) {
            $table->id();
            $table->integer('topic_id');
            $table->string('name');
            $table->string('stream_link');
            $table->integer('language')->default(1)->comment('[1 - english, 2 - spanish]');
            $table->integer('type')->default(1)->comment('[1 - youtube, 2 - cloudflare, 3 - zoom, 4 - vimeo]');
            $table->integer('status')->default(1)->comment('[0 - delete, 1 - draft, 2 - published]');
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
        Schema::dropIfExists('replay_videos');
    }
};
