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
        Schema::table('partnership_withdraws', function (Blueprint $table) {
            $table->string('admin_id')->default(0)->change();
            $table->integer('status')->default(1)->comment('[0 - deleted, 1 - active]')->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('partnership_withdraws', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
