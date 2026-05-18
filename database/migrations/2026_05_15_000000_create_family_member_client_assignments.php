<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_member_clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['family_member_id', 'client_id']);
            $table->index(['client_id', 'family_member_id']);
        });

        DB::table('family_members')
            ->select(['id', 'client_id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->get()
            ->each(function (object $member): void {
                DB::table('family_member_clients')->updateOrInsert([
                    'family_member_id' => $member->id,
                    'client_id' => $member->client_id,
                ], [
                    'is_primary' => true,
                    'created_at' => $member->created_at ?? now(),
                    'updated_at' => $member->updated_at ?? now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_member_clients');
    }
};
