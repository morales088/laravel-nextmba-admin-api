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
            $table->dateTime('end_date')->default(now()->addHours(4))->after('description');
            $table->dateTime('starting_date')->default(now())->after('description');
            // $table->date('date')->default(now())->after('description');

            $table->string('calendar_link')->after('description');
            $table->integer('topicId')->after('description');
            $table->string('live_url')->after('description');
            $table->string('chat_url')->after('description');            
            $table->integer('broadcast_status')->default(1)->after('end_date')->comment('[1 - upcoming, 2 - live, 3 - pending live, 4 - replay]');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->dropColumn('end_date');
            $table->dropColumn('starting_date');
            // $table->dropColumn('date');

            $table->dropColumn('calendar_link');
            $table->dropColumn('topic');
            $table->dropColumn('live_url');
            $table->dropColumn('chat_url');
            $table->dropColumn('broadcast');
        });
    }
};
