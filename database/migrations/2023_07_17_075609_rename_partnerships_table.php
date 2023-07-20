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
        if (!Schema::hasTable('affiliates')) {
            Schema::rename('partnerships', 'affiliates');

            Schema::dropIfExists('partnership_invites');
        }

        if (!Schema::hasTable('affiliate_withdraws')) {
            Schema::rename('partnership_withdraws', 'affiliate_withdraws');
        }

        if (Schema::hasColumn('events', 'partnership_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->renameColumn('partnership_id', 'affiliate_id');
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
        if (!Schema::hasTable('partnerships')) {
            Schema::rename('affiliates', 'partnerships');
        }

        if (!Schema::hasTable('partnership_withdraws')) {
            Schema::rename('affiliate_withdraws', 'partnership_withdraws');
        }

        if (Schema::hasColumn('events', 'affiliate_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->renameColumn('affiliate_id', 'partnership_id');
            });
        }
    }
};
