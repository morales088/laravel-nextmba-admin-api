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
        if (!Schema::hasColumn('video_libraries', 'type'))
        {
            Schema::table('video_libraries', function (Blueprint $table) {
                $table->integer('type')->default(1)->comment('[1 - main lecture, 2 - additional lecture]')->after('color');
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
            $table->dropColumn('type');
        });
    }
};
