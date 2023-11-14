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
        if (!Schema::hasColumn('video_libraries', 'vimeo_id_es')) {
            Schema::table('video_libraries', function (Blueprint $table) {
                $table->string('vimeo_id_es')->after('uid');
            });
        }

        if (!Schema::hasColumn('video_libraries', 'vimeo_id_pt')) {
            Schema::table('video_libraries', function (Blueprint $table) {
                $table->string('vimeo_id_pt')->after('vimeo_id_es');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('video_libraries', function (Blueprint $table) {
            $table->dropColumn('vimeo_id_pt');
            $table->dropColumn('vimeo_id_es');
        });
    }
};
