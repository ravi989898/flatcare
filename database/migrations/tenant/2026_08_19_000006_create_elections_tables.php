<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elections module — tenant database.
     *
     * There's no resident self-service portal yet, so this models an
     * admin-run election: the admin (or committee, at a physical polling
     * desk) records each vote on a resident's behalf rather than residents
     * casting votes themselves online. election_votes still enforces one
     * vote per resident per election via a unique constraint, and
     * election_candidates.votes_count is a denormalized counter kept in
     * sync at vote time so results don't require a COUNT() over every vote
     * on every page view.
     */
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

            $table->timestamp('nomination_start_at')->nullable();
            $table->timestamp('nomination_end_at')->nullable();
            $table->timestamp('voting_start_at')->nullable();
            $table->timestamp('voting_end_at')->nullable();

            $table->enum('status', ['draft', 'nominations_open', 'voting_open', 'closed', 'cancelled'])->default('draft');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('election_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->text('manifesto')->nullable();
            $table->unsignedInteger('votes_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['election_id', 'user_id']);
        });

        Schema::create('election_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('election_candidates')->cascadeOnDelete();
            $table->foreignId('voter_user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            // One vote per resident per election.
            $table->unique(['election_id', 'voter_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_votes');
        Schema::dropIfExists('election_candidates');
        Schema::dropIfExists('elections');
    }
};
