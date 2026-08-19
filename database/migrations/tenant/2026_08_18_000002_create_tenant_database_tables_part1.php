<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant Database migrations
     * These are run for each individual society database
     */
    public function up(): void
    {
        // Users Table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('profile_photo_path')->nullable();

            // Personal Info
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();

            // Address
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');
            $table->string('postal_code')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive', 'blocked', 'deleted'])->default('active');
            $table->string('blocked_reason')->nullable();
            $table->timestamp('blocked_at')->nullable();

            // Account Security
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();

            // Device & Token Management
            $table->string('remember_token')->nullable();

            // Additional
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('email');
            $table->index('phone');
            $table->index('created_at');
        });

        // Roles Table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'admin', 'resident', 'security'
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false); // Cannot be deleted
            $table->integer('priority')->default(0); // For role hierarchy

            $table->timestamps();
        });

        // Permissions Table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'maintenance.view', 'maintenance.pay'
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('module')->nullable(); // e.g., 'maintenance', 'visitor'

            $table->timestamps();
        });

        // User Roles
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');

            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
            $table->index('user_id');
            $table->index('role_id');
        });

        // Role Permissions
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');

            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // User Permissions (Direct permissions)
        Schema::create('permission_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');

            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
        });

        // Blocks
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Block A', 'Block B'
            $table->string('block_number')->unique(); // e.g., 'A', '1'
            $table->string('description')->nullable();
            $table->integer('total_flats')->default(0);
            $table->integer('total_floors')->nullable();
            $table->string('block_admin_contact')->nullable();

            $table->enum('status', ['active', 'inactive', 'under_construction'])->default('active');

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('block_number');
            $table->index('status');
        });

        // Flats
        Schema::create('flats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->onDelete('cascade');

            $table->string('flat_number')->unique();
            $table->string('floor_number');
            $table->enum('flat_type', ['1BHK', '2BHK', '3BHK', '4BHK', 'Duplex', 'Penthouse', 'Other'])->default('2BHK');
            $table->decimal('area_sqft', 8, 2)->nullable();

            // Ownership
            $table->enum('ownership_type', ['owned', 'rented', 'vacant'])->default('vacant');
            $table->string('owner_name')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive', 'under_construction', 'under_maintenance'])->default('active');

            // Additional
            $table->string('car_parking_slot')->nullable();
            $table->string('bike_parking_slot')->nullable();
            $table->json('amenities')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('block_id');
            $table->index('status');
            $table->index('ownership_type');
        });

        // Flat Residents (Occupants of flats)
        Schema::create('flat_residents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flat_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Type of resident
            $table->enum('resident_type', ['owner', 'tenant', 'occupant'])->default('owner');

            // Dates
            $table->date('moved_in_date')->nullable();
            $table->date('moved_out_date')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_primary')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['flat_id', 'user_id']);
            $table->index('resident_type');
            $table->index('status');
        });

        // Family Members
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->enum('relation', ['spouse', 'child', 'parent', 'sibling', 'other'])->default('other');
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('occupation')->nullable();
            $table->text('medical_info')->nullable(); // e.g., allergies

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('status');
        });

        // Vehicles
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->enum('vehicle_type', ['car', 'bike', 'scooter', 'cycle', 'commercial'])->default('car');
            $table->string('registration_number')->unique();
            $table->string('model')->nullable();
            $table->string('color')->nullable();
            $table->year('year')->nullable();
            $table->string('parking_slot')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('registration_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('family_members');
        Schema::dropIfExists('flat_residents');
        Schema::dropIfExists('flats');
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }
};
