<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Polls & Surveys — modeled after elections/election_candidates/
     * election_votes' proven shape (status lifecycle, one-vote-per-resident
     * unique constraint, denormalized vote counts) but with free-text
     * options instead of a candidate user_id, since a poll asks residents
     * to pick from arbitrary answers ("Yes"/"No"/"Option A") rather than
     * vote for a person standing for office.
     */
    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->id();

            $table->string('question');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->timestamp('closes_at')->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('votes_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });

        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voter_user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['poll_id', 'voter_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('poll_options');
        Schema::dropIfExists('polls');
    }
};
