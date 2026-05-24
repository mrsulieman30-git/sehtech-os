<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('parent_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('directories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('directories')->onDelete('cascade');
        });

        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('original_name');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->uuid('directory_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->enum('access_level', ['private', 'department', 'shared', 'company'])->default('private');
            $table->integer('version')->default(1);
            $table->uuid('parent_version_id')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->uuid('created_by')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('directory_id')->references('id')->on('directories')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('files', function (Blueprint $table) {
            $table->foreign('parent_version_id')->references('id')->on('files')->onDelete('set null');
        });

        Schema::create('file_permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('file_id');
            $table->uuid('user_id')->nullable();
            $table->uuid('role_id')->nullable();
            $table->enum('permission', ['view', 'edit']);
            $table->timestamps();

            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_permissions');
        Schema::dropIfExists('files');
        Schema::dropIfExists('directories');
    }
};
