<?php

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
        if (!Schema::hasTable('spam_blocks')) {
            Schema::create('spam_blocks', function (Blueprint $table) {
                $table->id();
                $table->string('factor_type', 50); // ip, email_domain, fingerprint, user_id
                $table->string('action_type', 50); // registration, post, comment, invitation
                $table->string('identifier'); // IP address, user ID, domain, fingerprint hash
                $table->timestamp('blocked_until')->nullable(); // When block expires
                $table->integer('attempt_count')->default(0); // Number of attempts before block
                $table->text('metadata')->nullable(); // Additional info (JSON)
                $table->timestamps();

                // Indexes for fast lookup
                $table->index(['factor_type', 'action_type', 'identifier']);
                $table->index('blocked_until'); // For cleanup of expired blocks
                $table->index(['factor_type', 'identifier']); // For unblock operations
            });
        }

        // Separate table for tracking attempts (optional, for analytics)
        if (!Schema::hasTable('spam_attempts')) {
            Schema::create('spam_attempts', function (Blueprint $table) {
                $table->id();
                $table->string('factor_type', 50);
                $table->string('action_type', 50);
                $table->string('identifier');
                $table->timestamp('attempted_at');
                $table->text('metadata')->nullable(); // Additional info (IP, user agent, etc.)
                $table->timestamps();

                // Indexes
                $table->index(['factor_type', 'action_type', 'identifier', 'attempted_at']);
                $table->index('attempted_at'); // For cleanup of old attempts
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spam_attempts');
        Schema::dropIfExists('spam_blocks');
    }
};
