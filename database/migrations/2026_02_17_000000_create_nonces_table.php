<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nonces', function (Blueprint $table) {
            $table->id();
            $table->string('nonce', 255)->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            
            $table->index(['nonce', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nonces');
    }
};
