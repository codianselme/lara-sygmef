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
        Schema::create('emecf_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emecf_invoice_id')->constrained()->onDelete('cascade');
            $table->string('code')->nullable(); // Code d'article
            $table->string('name'); // Nom d'article
            $table->integer('price'); // Prix unitaire
            $table->decimal('quantity', 10, 3); // Quantité
            $table->char('tax_group', 1); // Groupe de taxation (A, B, C, D, E, F)
            $table->integer('tax_specific')->nullable(); // Taxe spécifique
            $table->integer('original_price')->nullable(); // Prix d'origine
            $table->string('price_modification')->nullable(); // Description modification
            $table->timestamps();
            
            // Index
            $table->index('emecf_invoice_id');
            $table->index('tax_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emecf_invoice_items');
    }
};
