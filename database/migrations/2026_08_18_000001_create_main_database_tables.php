<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Main database migrations for FlatCare platform
     * This creates tables for super admin and multi-tenant management
     */
    public function up(): void
    {
        // Super Admin Users
        Schema::create('super_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        // Societies Registry
        Schema::create('societies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('alternate_phone')->nullable();

            // Address
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('country')->default('India');
            $table->string('postal_code');

            // Details
            $table->string('registration_number')->nullable();
            $table->text('logo_path')->nullable();
            $table->integer('total_flats')->nullable();
            $table->integer('total_blocks')->nullable();

            // Usage Period
            $table->date('start_date');
            $table->date('end_date');

            // Status
            $table->enum('status', ['active', 'inactive', 'expired', 'archived'])->default('active');
            $table->boolean('is_trial')->default(false);
            $table->boolean('payment_verified')->default(false);

            // Database Connection
            $table->string('db_name')->unique();

            // Admin Info
            $table->string('admin_name')->nullable();
            $table->string('admin_email')->nullable();
            $table->string('admin_phone')->nullable();

            // Metadata
            $table->json('settings')->nullable();
            $table->foreignId('created_by_super_admin')->nullable()->constrained('super_admins')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
        });

        // Society Database Credentials (Encrypted)
        Schema::create('society_databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained()->onDelete('cascade');

            // Database connection details (will be encrypted)
            $table->string('db_host');
            $table->string('db_port')->default('3306');
            $table->string('db_name');
            $table->string('db_user');
            $table->text('db_password'); // Encrypted
            $table->string('db_charset')->default('utf8mb4');
            $table->string('db_collation')->default('utf8mb4_unicode_ci');

            // Status
            $table->enum('status', ['active', 'creating', 'created', 'failed'])->default('creating');
            $table->text('error_message')->nullable();

            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('last_migrated_at')->nullable();

            $table->unique(['society_id', 'db_name']);
        });

        // Modules (Features available in platform)
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'maintenance', 'visitor', 'complaint'
            $table->string('display_name'); // e.g., 'Maintenance Management'
            $table->text('description');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_core')->default(false); // Cannot be disabled
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // Permissions (Global permissions)
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'society.create', 'society.update'
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->foreignId('module_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });

        // Society Modules (Which modules are enabled for each society)
        Schema::create('society_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->constrained()->onDelete('cascade');

            // Configuration
            $table->boolean('is_enabled')->default(true);
            $table->json('configuration')->nullable(); // Module-specific settings
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();

            $table->timestamps();

            $table->unique(['society_id', 'module_id']);
        });

        // Super Admin Permissions
        Schema::create('super_admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');

            $table->timestamps();

            $table->unique(['super_admin_id', 'permission_id']);
        });

        // Payment Webhooks (Razorpay)
        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->nullable()->constrained()->nullOnDelete();

            // Webhook data
            $table->string('event_type'); // e.g., 'payment.authorized'
            $table->string('razorpay_payment_id')->unique();
            $table->string('razorpay_order_id');
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('INR');

            // Verification
            $table->boolean('signature_verified')->default(false);
            $table->text('signature')->nullable();
            $table->json('payload');

            // Processing
            $table->enum('status', ['received', 'processing', 'processed', 'failed', 'ignored'])->default('received');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index('event_type');
            $table->index('razorpay_payment_id');
            $table->index('status');
        });

        // Audit Logs (Main platform)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('society_id')->nullable()->constrained()->nullOnDelete();

            // Action details
            $table->string('action'); // e.g., 'society.created', 'society.updated'
            $table->string('module'); // e.g., 'society_management', 'payment'
            $table->string('entity_type')->nullable(); // e.g., 'Society', 'PaymentWebhook'
            $table->unsignedBigInteger('entity_id')->nullable();

            // Changes
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request info
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            $table->index('action');
            $table->index('module');
            $table->index(['super_admin_id', 'created_at']);
            $table->index(['society_id', 'created_at']);
        });

        // Notification Queue (For processing)
        Schema::create('notifications_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->nullable()->constrained()->nullOnDelete();

            // Recipient
            $table->string('recipient_type'); // 'user', 'block', 'society'
            $table->unsignedBigInteger('recipient_id')->nullable();

            // Notification content
            $table->string('type'); // e.g., 'payment_due', 'visitor_waiting'
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();

            // Status
            $table->enum('status', ['pending', 'sent', 'failed', 'skipped'])->default('pending');
            $table->text('error_message')->nullable();

            // Channels
            $table->boolean('send_push')->default(true);
            $table->boolean('send_email')->default(false);
            $table->boolean('send_sms')->default(false);

            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
            $table->index(['society_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_queue');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('payment_webhooks');
        Schema::dropIfExists('super_admin_permissions');
        Schema::dropIfExists('society_modules');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('society_databases');
        Schema::dropIfExists('societies');
        Schema::dropIfExists('super_admins');
    }
};
