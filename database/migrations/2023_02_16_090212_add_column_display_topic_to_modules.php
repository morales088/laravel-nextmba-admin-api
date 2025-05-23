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
        if (!Schema::hasColumn('modules', 'display_topic'))
        {
            Schema::table('modules', function (Blueprint $table) {
                $table->integer('display_topic')->default(0)->comment('[0 - none, 1 - display]')->after('calendar_link');
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
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('display_topic');
        });
    }
};
