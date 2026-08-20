<?php

use App\Enums\RegistrationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('status', 20)->default(RegistrationStatus::Pending->value);
            $table->unsignedSmallInteger('bib_number')->nullable();
            $table->string('phone', 40);
            $table->date('birth_date');
            $table->string('emergency_contact_name', 120);
            $table->string('emergency_contact_phone', 40);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->unique(['event_id', 'bib_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
