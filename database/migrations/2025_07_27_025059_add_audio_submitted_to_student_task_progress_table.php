<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_task_progress', function (Blueprint $table) {
            $table->boolean('audio_submitted')->default(false)->after('completed');
        });
    }

    public function down(): void
    {
        Schema::table('student_task_progress', function (Blueprint $table) {
            $table->dropColumn('audio_submitted');
        });
    }

};
