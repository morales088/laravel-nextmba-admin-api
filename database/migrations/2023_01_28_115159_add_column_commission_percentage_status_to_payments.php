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
            $table->string('affiliate_code')->after('url');
            $table->decimal('commission_percentage', 5, 2)->after('affiliate_code');
            $table->integer('commission_status')->default(0)->comment('[0 - unpaid, 1 - paid]')->after('commission_percentage');
            $table->integer('from_student_id')->comment('student owns invitation_code')->after('commission_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('affiliate_code');
            $table->dropColumn('commission_percentage');
            $table->dropColumn('commission_status');
            $table->dropColumn('from_student_id');
        });
    }
};
