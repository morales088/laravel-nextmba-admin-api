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
        Schema::create('module_streams', function (Blueprint $table) {
            $table->id();
            $table->integer('module_id');
            $table->string('name');
            $table->string('key');
            $table->string('chat_link')->nullable();
            $table->integer('language')->default(1)->comment('[1 - english, 2 - spanish]');
            $table->integer('type')->default(1)->comment('[1 - youtube, 2 - cloudflare, 3 - zoom, 4 - vimeo]');
            $table->integer('broadcast_status')->default(1)->comment('[0 - starting live, 1 - offline, 2 - live, 3 - pending replay, 4 - replay]');
            $table->integer('status')->default(1)->comment('[1 - daft, 2 - published, 3 - archived]');
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
        Schema::dropIfExists('module_streams');
    }
};
