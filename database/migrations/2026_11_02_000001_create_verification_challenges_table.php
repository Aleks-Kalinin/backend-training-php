<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One table serves every OTP flow in the specs: registration (01), login (03),
     * email change (06), account deletion (07) and password reset (13). The flow is
     * identified by the `purpose` column.
     *
     * Magic links are out of scope for now; when they are added this table gains a
     * `channel` column and `code_hash` holds the token hash instead.
     */
    public function up(): void
    {
        Schema::create('verification_challenges', function (Blueprint $table) {
            // This id is the opaque `attemptId` returned to the client.
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // App\Enums\ChallengePurpose
            $table->string('purpose')->index();

            // NEVER store the OTP itself - only a hash of it (spec 01 section 3.1).
            $table->string('code_hash');

            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('last_sent_at')->nullable();

            // Single-use: set once, and only via an atomic conditional update.
            $table->timestamp('consumed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'purpose', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_challenges');
    }
};
