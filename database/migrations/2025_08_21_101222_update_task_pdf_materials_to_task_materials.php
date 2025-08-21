<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Rename the table
        Schema::rename('task_pdf_materials', 'task_materials');
        
        // Add new columns and modify existing ones
        Schema::table('task_materials', function (Blueprint $table) {
            // Rename columns
            $table->renameColumn('pdf_title', 'material_title');
            $table->renameColumn('pdf_file_path', 'material_file_path');
            
            // Add new columns
            $table->enum('material_type', ['pdf', 'image', 'video', 'audio', 'document', 'archive'])
                  ->default('pdf')
                  ->after('material_file_path');
            $table->bigInteger('file_size')->nullable()->after('material_type');
            
            // Modify the uploaded_at column if needed (if it doesn't have useCurrent())
            $table->timestamp('uploaded_at')->useCurrent()->change();
        });
    }

    public function down()
    {
        Schema::table('task_materials', function (Blueprint $table) {
            // Reverse the changes
            $table->renameColumn('material_title', 'pdf_title');
            $table->renameColumn('material_file_path', 'pdf_file_path');
            $table->dropColumn(['material_type', 'file_size']);
        });
        
        // Rename back to original
        Schema::rename('task_materials', 'task_pdf_materials');
    }
};