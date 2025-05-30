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
        Schema::table('modules', function (Blueprint $table) {
            $table->string('uid')->after('live_url')->nullable();
            $table->string('stream_info')->after('uid')->nullable();
            $table->string('stream_json')->after('stream_info')->nullable();
            $table->string('srt_url')->after('stream_json')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('uid');
            $table->dropColumn('stream_info');
            $table->dropColumn('stream_json');
        });
    }
};
