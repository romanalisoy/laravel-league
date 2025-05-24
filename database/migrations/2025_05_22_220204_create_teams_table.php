<?php

use App\Models\Team;
use Database\Seeders\LeagueSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Team::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('strength')->default(50);
            $table->timestamps();
        });

        Artisan::call('db:seed', [
            '--class' => LeagueSeeder::class,
            '--force' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Team::TABLE);
    }
};
