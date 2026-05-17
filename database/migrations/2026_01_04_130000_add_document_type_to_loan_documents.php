<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_documents', function (Blueprint $table) {
            $table->string('document_type')->default('other')->after('loan_id');
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            
            $table->index(['loan_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::table('loan_documents', function (Blueprint $table) {
            $table->dropIndex(['loan_id', 'document_type']);
            $table->dropColumn(['document_type', 'tenant_id']);
        });
    }
};
