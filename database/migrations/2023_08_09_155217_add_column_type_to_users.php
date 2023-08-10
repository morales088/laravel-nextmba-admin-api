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
        if (!Schema::hasColumn('users', 'role')) {

            Schema::table('users', function (Blueprint $table) {
                $table->integer('role')->default(1)->comment('[1 - admin, 2 - partners]')->after('password');
            });
        }

        if (!Schema::hasColumn('users', 'type')) {
            
            Schema::table('users', function (Blueprint $table) {
                $table->integer('type')->default(1)->comment('[1 - full, 2 - students/payments]')->after('role');
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
        Schema::table('users', function (Blueprint $table) {

            if (Schema::hasColumn('users', 'type')) {
                $table->dropColumn('type');
            }
            
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
