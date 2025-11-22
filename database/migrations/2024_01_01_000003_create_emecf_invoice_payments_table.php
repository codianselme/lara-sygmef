<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emecf_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emecf_invoice_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Type de paiement (ESPECES, VIREMENT, etc.)
            $table->integer('amount'); // Montant
            $table->timestamps();
            
            // Index
            $table->index('emecf_invoice_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emecf_invoice_payments');
    }
};
