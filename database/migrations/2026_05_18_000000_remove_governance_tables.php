<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'governance.manage')->value('id');

        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permission_user')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::dropIfExists('governance_actions');
        Schema::dropIfExists('governance_meetings');
        Schema::dropIfExists('governance_policies');
        Schema::dropIfExists('gdpr_cases');
        Schema::dropIfExists('governance_complaints');
    }

    public function down(): void
    {
        //
    }
};
