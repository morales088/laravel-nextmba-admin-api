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
        Schema::table('partnership_invites', function (Blueprint $table) {
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete()->after('invitation_code');
            $table->decimal('commission_percent', 5, 2)->after('from_student_id');
            $table->integer('commission_amount')->after('from_student_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('partnership_invites', function (Blueprint $table) {
            $table->dropForeign('partnership_invites_payment_id_foreign');
            $table->dropColumn('payment_id');
            $table->dropColumn('commission_amount');
            $table->dropColumn('commission_percent');
        });
    }
};
