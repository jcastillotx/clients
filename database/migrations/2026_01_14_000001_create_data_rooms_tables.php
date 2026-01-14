<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data Rooms Migration
 *
 * Creates tables for private data rooms with:
 * - AES-256 encryption at rest
 * - File isolation per data room
 * - Granular role-based access controls
 * - SOC2 Type II compliance audit trail
 */
return new class extends Migration
{
    public function up(): void
    {
        // Data Rooms - Isolated secure storage containers
        Schema::create('data_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();

            // Encryption settings
            $table->text('encryption_key')->comment('Encrypted room key (AES-256)');
            $table->string('encryption_algorithm')->default('aes-256-gcm');
            $table->string('key_derivation_salt')->nullable();

            // Storage settings
            $table->string('storage_provider')->default('s3'); // s3, local
            $table->string('storage_bucket')->nullable();
            $table->string('storage_prefix')->nullable(); // Isolated path prefix

            // Status and settings
            $table->enum('status', ['active', 'archived', 'locked'])->default('active');
            $table->boolean('require_2fa')->default(true);
            $table->boolean('require_watermark')->default(false);
            $table->boolean('allow_download')->default(true);
            $table->boolean('allow_print')->default(false);
            $table->integer('session_timeout_minutes')->default(30);

            // Compliance
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lock_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index(['slug']);
        });

        // Data Room Folders - Organize files within data rooms
        Schema::create('data_room_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_room_id')->constrained('data_rooms')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('data_room_folders')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->string('path'); // Full path for quick lookups
            $table->integer('depth')->default(0);

            $table->timestamps();

            $table->index(['data_room_id', 'parent_id']);
            $table->index(['path']);
        });

        // Data Room Files - Encrypted files with metadata
        Schema::create('data_room_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_room_id')->constrained('data_rooms')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('data_room_folders')->nullOnDelete();

            // File information
            $table->string('name');
            $table->string('original_filename');
            $table->string('storage_path'); // Path in S3/storage
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');

            // Encryption metadata (stored separately from key)
            $table->string('encryption_iv'); // Initialization vector
            $table->string('encryption_tag'); // GCM authentication tag
            $table->string('checksum'); // SHA-256 hash of original content
            $table->string('encrypted_checksum'); // Hash of encrypted content

            // Versioning
            $table->integer('version')->default(1);
            $table->foreignId('parent_version_id')->nullable()->constrained('data_room_files')->nullOnDelete();

            // Status
            $table->enum('status', ['active', 'archived', 'deleted'])->default('active');
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['data_room_id', 'status']);
            $table->index(['folder_id']);
            $table->index(['storage_path']);
        });

        // Data Room Access - Granular RBAC
        Schema::create('data_room_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_room_id')->constrained('data_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete();

            // Permission levels
            $table->enum('permission_level', [
                'view',        // Can view files
                'download',    // Can download files
                'upload',      // Can upload new files
                'edit',        // Can edit/replace files
                'delete',      // Can delete files
                'manage',      // Full management including access control
                'admin',       // Full admin including room settings
            ])->default('view');

            // Specific permissions (granular override)
            $table->boolean('can_view')->default(true);
            $table->boolean('can_download')->default(false);
            $table->boolean('can_upload')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_share')->default(false);
            $table->boolean('can_manage_access')->default(false);

            // Access restrictions
            $table->json('allowed_ips')->nullable(); // IP allowlist
            $table->json('allowed_folders')->nullable(); // Folder-level restrictions
            $table->timestamp('expires_at')->nullable();
            $table->boolean('require_2fa')->default(true);

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_accessed_at')->nullable();
            $table->integer('access_count')->default(0);

            $table->timestamps();

            $table->unique(['data_room_id', 'user_id']);
            $table->index(['user_id', 'is_active']);
            $table->index(['expires_at']);
        });

        // Data Room Activity Log - SOC2 Audit Trail
        Schema::create('data_room_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_room_id')->constrained('data_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('file_id')->nullable()->constrained('data_room_files')->nullOnDelete();

            // Activity details
            $table->string('action'); // view, download, upload, edit, delete, share, access_granted, etc.
            $table->string('resource_type'); // file, folder, access, room
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('details')->nullable(); // Additional context

            // Security context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->boolean('was_2fa_verified')->default(false);

            // Result
            $table->enum('status', ['success', 'failed', 'blocked'])->default('success');
            $table->string('failure_reason')->nullable();

            $table->timestamp('created_at');

            $table->index(['data_room_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['file_id']);
        });

        // Data Room Invitations - Pending access invitations
        Schema::create('data_room_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_room_id')->constrained('data_rooms')->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();

            $table->string('email');
            $table->string('token', 64)->unique();
            $table->enum('permission_level', ['view', 'download', 'upload', 'edit', 'manage'])->default('view');
            $table->json('permissions')->nullable(); // Granular permissions

            $table->enum('status', ['pending', 'accepted', 'expired', 'revoked'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index(['token']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_room_invitations');
        Schema::dropIfExists('data_room_activity_logs');
        Schema::dropIfExists('data_room_access');
        Schema::dropIfExists('data_room_files');
        Schema::dropIfExists('data_room_folders');
        Schema::dropIfExists('data_rooms');
    }
};
