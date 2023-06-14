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
        Schema::table('module_streams', function (Blueprint $table) {
            $table->integer('status')->default(1)->comment('[1 - draft, 2 - ready, 3 - live, 4 - offline]')->change();
        });
        
        if (Schema::hasColumn('module_streams', 'broadcast_status'))
        {
            Schema::table('module_streams', function (Blueprint $table) {
                $table->dropColumn('broadcast_status');
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
        Schema::table('module_streams', function (Blueprint $table) {
            $table->integer('status')->default(1)->comment('[1 - daft, 2 - published, 3 - archived]')->change();
        });
    }
};
