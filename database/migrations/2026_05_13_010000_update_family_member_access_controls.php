<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            if (! Schema::hasColumn('family_members', 'can_view_appointments')) {
                $table->boolean('can_view_appointments')->default(false)->after('can_receive_incident_alerts');
            }

            if (! Schema::hasColumn('family_members', 'can_view_visits')) {
                $table->boolean('can_view_visits')->default(false)->after('can_view_appointments');
            }

            if (! Schema::hasColumn('family_members', 'can_view_staff_messages')) {
                $table->boolean('can_view_staff_messages')->default(false)->after('can_upload_documents');
            }
        });

        if (Schema::hasColumn('family_members', 'can_message_staff')) {
            Schema::table('family_members', function (Blueprint $table) {
                $table->dropColumn('can_message_staff');
            });
        }
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            if (! Schema::hasColumn('family_members', 'can_message_staff')) {
                $table->boolean('can_message_staff')->default(false)->after('can_upload_documents');
            }
        });

        if (Schema::hasColumn('family_members', 'can_view_staff_messages')) {
            Schema::table('family_members', function (Blueprint $table) {
                $table->dropColumn('can_view_staff_messages');
            });
        }

        if (Schema::hasColumn('family_members', 'can_view_visits')) {
            Schema::table('family_members', function (Blueprint $table) {
                $table->dropColumn('can_view_visits');
            });
        }

        if (Schema::hasColumn('family_members', 'can_view_appointments')) {
            Schema::table('family_members', function (Blueprint $table) {
                $table->dropColumn('can_view_appointments');
            });
        }
    }
};
