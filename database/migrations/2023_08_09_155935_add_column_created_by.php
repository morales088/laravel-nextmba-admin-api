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
        if (!Schema::hasColumn('students', 'created_by')) {

            Schema::table('students', function (Blueprint $table) {
                $table->integer('created_by')->default(0)->after('last_login');
            });
        }

        if (!Schema::hasColumn('payments', 'created_by')) {
            
            Schema::table('payments', function (Blueprint $table) {
                $table->integer('created_by')->default(0)->after('product_code');
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
        if (Schema::hasColumn('students', 'created_by')) {

            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('created_by');
            });
        }
    
        if (Schema::hasColumn('payments', 'created_by')) {
            
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('created_by');
            });
        }
    }
};
