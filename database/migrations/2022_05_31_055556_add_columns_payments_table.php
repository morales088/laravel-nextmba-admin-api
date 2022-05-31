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
        Schema::table('payments', function (Blueprint $table) {
            $table->renameColumn('payment_id', 'reference_id');
            $table->string('country')->after('payment_method');
            $table->string('last_name')->after('payment_method');
            $table->string('first_name')->after('payment_method');
            $table->string('phone')->after('payment_method');
            $table->string('email')->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->dropColumn('country');
            $table->dropColumn('last_name');
            $table->dropColumn('first_name');
            $table->dropColumn('phone');
            $table->dropColumn('email');
        });
    }
};
