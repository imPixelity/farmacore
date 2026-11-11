<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (DB::table('users')->select('id', 'name')->orderBy('id')->cursor() as $user) {
            if (mb_strlen($user->name) > 100) {
                throw new RuntimeException("User {$user->id} has a name longer than 100 characters; shorten it before migrating.");
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->after('id');
            $table->boolean('is_active')->default(true)->after('password');
            $table->unsignedTinyInteger('failed_attempts')->default(0)->after('is_active');
            $table->timestamp('locked_until')->nullable()->after('failed_attempts');
            $table->timestamp('last_login_at')->nullable()->after('locked_until');
        });

        DB::table('users')->orderBy('id')->each(function (object $user): void {
            $username = Str::of(Str::before($user->email, '@'))
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9._-]+/', '-')
                ->trim('.-_')
                ->limit(max(1, 49 - strlen((string) $user->id)), '')
                ->value();

            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => ($username ?: 'user').'.'.$user->id]);

        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable(false)->unique()->change();
            $table->string('name', 100)->change();
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('users')->whereNull('email')->exists()) {
            throw new RuntimeException('Cannot restore required email while users have no email address.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'is_active', 'failed_attempts', 'locked_until', 'last_login_at']);
            $table->string('name')->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
