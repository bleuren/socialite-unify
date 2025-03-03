<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('provider_name');
            $table->string('provider_id');
            $table->timestamps();
            $table->unique(['provider_name', 'provider_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('social_accounts');
    }
};
