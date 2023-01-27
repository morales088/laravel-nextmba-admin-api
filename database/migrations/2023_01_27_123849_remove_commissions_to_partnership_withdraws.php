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
            $table->dropForeign('partnership_withdraws_payment_id_foreign');
            $table->dropColumn(['payment_id','commission_percent']);
            $table->renameColumn('commission_amount', 'withdraw_amount')->after('student_id');
            $table->string('remarks')->after('admin_id');
            $table->string('withdraw_method')->after('admin_id');
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
            $table->dropColumn(['withdraw_method', 'remarks']);
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->decimal('commission_percent', 5, 2);
            $table->renameColumn('withdraw_amount', 'commission_amount');
        });
    }
};
