<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable()->after('name');
        });

        $used = [];
        DB::table('users')
            ->select('id', 'email', 'name')
            ->orderBy('id')
            ->chunkById(500, function ($users) use (&$used) {
                foreach ($users as $user) {
                    $base = strtolower(strtok($user->email, '@') ?: $user->name);
                    $base = preg_replace('/[^a-z0-9_-]/', '', $base) ?: 'user';
                    $base = substr($base, 0, 30);
                    $username = $base;
                    $suffix = 1;

                    while (isset($used[$username])) {
                        $ending = '-'.$suffix++;
                        $username = substr($base, 0, 30 - strlen($ending)).$ending;
                    }

                    $used[$username] = true;
                    DB::table('users')->where('id', $user->id)->update(['username' => $username]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable(false)->change();
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });
    }
};
