<?php

use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // UUID primary keys: specs-php/00-platform-stack/spec.md section 5.
            $table->uuid('id')->primary();

            // Registration collects an email and a password only. Profile fields
            // (name, photo) arrive with specs 05/06, so they are nullable here.
            $table->string('name')->nullable();

            // Normalized (trimmed + lowercased) email. There is deliberately no
            // second "raw email" column: the normalized value is the stored value.
            $table->string('email')->unique();

            $table->string('password');

            // Authoritative account state. email_verified_at only records *when*
            // a verification challenge was completed.
            $table->string('status')->default(UserStatus::Pending->value)->index();
            $table->timestamp('email_verified_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // Matches the users.id UUID type above.
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
