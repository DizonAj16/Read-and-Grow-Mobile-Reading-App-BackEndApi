<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('task_materials', function (Blueprint $table) {
            // Add description column
            $table->text('description')->nullable()->after('material_title');

            // Add timestamps if they don’t already exist
            if (!Schema::hasColumn('task_materials', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down()
    {
        Schema::table('task_materials', function (Blueprint $table) {
            $table->dropColumn('description');
            // You could also drop timestamps if you want rollback to be clean
            $table->dropTimestamps();
        });
    }
};
