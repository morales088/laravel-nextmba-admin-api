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
            $table->renameColumn('pro_access', 'account_type');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->integer('account_type')->default(0)->comment('[1 - trial, 2 - regular, 3 - pro]')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->renameColumn('account_type', 'pro_access');
        });
    }
};
