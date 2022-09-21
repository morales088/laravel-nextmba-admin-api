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
        Schema::table('students', function (Blueprint $table) {
            $table->integer('updated_by')->default(1)->after('field');
            $table->dateTime('last_login')->default(now())->after('field');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->dropColumn('updated_by');
            $table->dropColumn('last_login');
        });
    }
};
