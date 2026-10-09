<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'attachment_url')) {
                $table->text('attachment_url')->nullable()->after('sender_type');
            }
            if (!Schema::hasColumn('messages', 'attachment_type')) {
                $table->string('attachment_type')->nullable()->after('attachment_url');
            }
            if (!Schema::hasColumn('messages', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_type');
            }
            if (!Schema::hasColumn('messages', 'metadata')) {
                $table->json('metadata')->nullable()->after('attachment_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('messages', 'attachment_name')) {
                $table->dropColumn('attachment_name');
            }
            if (Schema::hasColumn('messages', 'attachment_type')) {
                $table->dropColumn('attachment_type');
            }
            if (Schema::hasColumn('messages', 'attachment_url')) {
                $table->dropColumn('attachment_url');
            }
        });
    }
};
