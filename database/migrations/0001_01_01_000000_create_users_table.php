<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('role', ['Admin', 'Normal_User'])->default('Normal_User');
            $table->string('email')->unique();
            $table->string('public_reference_id')->unique()->nullable();
            $table->string('avatar')->default(config('chatify.user_avatar.default'));
            $table->string('gender')->nullable();
            $table->string('phone_number')->nullable()->unique();
            $table->string('country')->nullable();
            $table->enum('account_status',['Active', 'Inactive', 'Suspended'])->default('Inactive');
            $table->longText('suspension_note')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->foreignId('user_ref')->primary();
            $table->string('token_signature');
            $table->string('token_type');
            $table->timestamp("expires_at");
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        DB::table('users')->insert([
            'first_name' => "Theadmin",
            'last_name' => "Test",
            'email' => "admintrustexplorer@test.com",
            'role' => 'Admin',
            'email_verified_at' => now(),
            'password' => app('hash')->make('password'),
            'public_reference_id' => '61ea9086bf786yk',
            'avatar' => config('chatify.user_avatar.default'),
            'account_status' => 'Active',
            'email_verified_at' => Carbon::now(),
          ]);
      
          DB::table('users')->insert([
            'first_name' => "Demo",
            'last_name' => "User1",
            'email' => "monnifysupport@test.com",
            'role' => 'Normal_User',
            'email_verified_at' => now(),
            'password' => app('hash')->make('monnifysuportpassword'),
            'public_reference_id' => '6uy2be9187bb736',
            'avatar' => config('chatify.user_avatar.default'),
            'account_status' => 'Active',
            'email_verified_at' => Carbon::now(),
          ]);
      
          DB::table('users')->insert([
            'first_name' => "Monnify",
            'last_name' => "Client1",
            'email' => "monnifysupportclient@test.com",
            'role' => 'Normal_User',
            'email_verified_at' => now(),
            'password' => app('hash')->make('monnifysuportclientpassword'),
            'public_reference_id' => '65b79987ba736',
            'avatar' => config('chatify.user_avatar.default'),
            'account_status' => 'Active',
            'email_verified_at' => Carbon::now(),
          ]);
      
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('sessions');
    }
};
