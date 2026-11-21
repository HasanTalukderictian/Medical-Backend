<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Je role e user ache sheta delete kora jabe na (restrictOnDelete)
            $table->foreignId('role_id')->nullable()->after('password')
                ->constrained('roles')->restrictOnDelete();
        });

        // "active" column age thekei thakle abar add korbe na
        if (! Schema::hasColumn('users', 'active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('role_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
