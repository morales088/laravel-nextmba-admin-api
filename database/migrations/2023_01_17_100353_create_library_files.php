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
        Schema::create('library_files', function (Blueprint $table) {
            $table->id();
            $table->integer('libraryId');
            $table->string('name');
            $table->string('link')->nullable();
            $table->integer('status')->default(1)->comment('[0 - deleted, 1 - active]');
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
        Schema::dropIfExists('library_files');
    }
};
