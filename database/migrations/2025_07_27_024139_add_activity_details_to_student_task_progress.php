<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('student_task_progress', function (Blueprint $table) {
            $table->json('activity_details')->nullable()->after('max_score');
        });
    }

    public function down()
    {
        Schema::table('student_task_progress', function (Blueprint $table) {
            $table->dropColumn('activity_details');
        });
    }

};
