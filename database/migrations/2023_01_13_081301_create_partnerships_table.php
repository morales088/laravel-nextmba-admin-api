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
        Schema::create('partnerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('admin_id');
            // $table->foreign('admin_id')->references('id')->on('users');
            $table->string('affiliate_code');
            $table->integer('affiliate_status')->default(0)->comment('[0 - pending, 1 - approved, 2 - disapproved]');
            $table->integer('status')->default(1)->comment('[0 - deleted, 1 - active]');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partnerships');
    }
};
