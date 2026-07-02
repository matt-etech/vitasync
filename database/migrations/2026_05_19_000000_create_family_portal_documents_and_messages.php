<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('family_portal_documents')) {
            Schema::create('family_portal_documents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->foreignId('uploaded_by_family_member_id')->nullable()->constrained('family_members')->nullOnDelete();
                $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('display_name');
                $table->string('original_filename');
                $table->string('file_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('category')->nullable();
                $table->boolean('is_sensitive')->default(false);
                $table->boolean('shared_with_family')->default(true);
                $table->timestamp('uploaded_at')->nullable();
                $table->timestamps();

                $table->index(['client_id', 'shared_with_family', 'is_sensitive'], 'fp_docs_client_shared_sensitive_idx');
            });
        } elseif (! Schema::hasIndex('family_portal_documents', 'fp_docs_client_shared_sensitive_idx')) {
            Schema::table('family_portal_documents', function (Blueprint $table): void {
                $table->index(['client_id', 'shared_with_family', 'is_sensitive'], 'fp_docs_client_shared_sensitive_idx');
            });
        }

        if (! Schema::hasTable('family_portal_messages')) {
            Schema::create('family_portal_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('client_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('subject');
                $table->text('message');
                $table->boolean('visible_to_family')->default(true);
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['client_id', 'visible_to_family', 'sent_at'], 'fp_msgs_client_visible_sent_idx');
            });
        } elseif (! Schema::hasIndex('family_portal_messages', 'fp_msgs_client_visible_sent_idx')) {
            Schema::table('family_portal_messages', function (Blueprint $table): void {
                $table->index(['client_id', 'visible_to_family', 'sent_at'], 'fp_msgs_client_visible_sent_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('family_portal_messages');
        Schema::dropIfExists('family_portal_documents');
    }
};
