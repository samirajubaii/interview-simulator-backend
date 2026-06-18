<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('interview_results', function (Blueprint $table) {
        $table->string('category_id')->nullable()->after('session_id');
        $table->string('difficulty')->nullable()->after('category_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('interview_results', function (Blueprint $table) {
            $table->dropColumn(['category_id', 'difficulty']);
        });
    }
};
