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
        if (!Schema::hasColumn('modules', 'featuredSpeakerId')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->integer('featuredSpeakerId')->default(0)->after('topicId');
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
        if (Schema::hasColumn('modules', 'featuredSpeakerId')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->dropColumn('featuredSpeakerId');
            });
        }
    }
};
