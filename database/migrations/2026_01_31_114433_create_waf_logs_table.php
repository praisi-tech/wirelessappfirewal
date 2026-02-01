<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waf_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('url');
            $table->string('method', 10);
            $table->json('request_data')->nullable();
            $table->string('threat_type')->nullable();
            $table->text('description');
            $table->integer('severity')->default(1); // 1=Low, 2=Medium, 3=High, 4=Critical
            $table->boolean('blocked')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            
            $table->index(['ip_address', 'created_at']);
            $table->index('threat_type');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waf_logs');
    }
};