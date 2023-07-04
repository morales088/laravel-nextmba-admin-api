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
        Schema::table('module_files', function (Blueprint $table) {
            $table->integer('type')->default(0)->comment('[0 - speaker, 1 - assignments]')->after('link');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('module_files', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
