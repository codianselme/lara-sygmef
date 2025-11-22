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
        Schema::create('emecf_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique(); // UUID de la transaction
            $table->string('ifu', 13); // IFU du vendeur
            $table->char('aib', 1)->nullable(); // Type AIB (A ou B)
            $table->char('type', 2); // Type de facture (FV, EV, FA, EA)
            $table->string('reference', 24)->nullable(); // Référence pour factures d'avoir
            $table->string('operator_id')->nullable(); // ID de l'opérateur
            $table->string('operator_name'); // Nom de l'opérateur
            $table->string('client_ifu', 13)->nullable(); // IFU du client
            $table->string('client_name')->nullable(); // Nom du client
            $table->string('client_contact')->nullable(); // Contact du client
            $table->text('client_address')->nullable(); // Adresse du client
            
            // Champs de calcul des taxes
            $table->integer('ta')->default(0); // Valeur groupe A
            $table->integer('tb')->default(0); // Valeur groupe B
            $table->integer('tc')->default(0); // Valeur groupe C
            $table->integer('td')->default(0); // Valeur groupe D
            $table->integer('taa')->default(0); // Montant total groupe A
            $table->integer('tab')->default(0); // Montant total groupe B
            $table->integer('tac')->default(0); // Montant total groupe C
            $table->integer('tad')->default(0); // Montant total groupe D
            $table->integer('tae')->default(0); // Montant total groupe E
            $table->integer('taf')->default(0); // Montant total groupe F
            $table->integer('hab')->default(0); // Montant HT groupe B
            $table->integer('had')->default(0); // Montant HT groupe D
            $table->integer('vab')->default(0); // Montant TVA groupe B
            $table->integer('vad')->default(0); // Montant TVA groupe D
            $table->integer('aib_amount')->default(0); // Montant AIB
            $table->integer('ts')->default(0); // Montant impôt spécifique
            $table->integer('total')->default(0); // Montant total
            
            // Champs de finalisation
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'error'])->default('pending');
            $table->string('code_mec_ef_dgi', 29)->nullable(); // Code MECeF/DGI
            $table->text('qr_code')->nullable(); // Contenu du code QR
            $table->string('date_time', 19)->nullable(); // Date/heure facture
            $table->string('counters')->nullable(); // Compteurs
            $table->string('nim', 10)->nullable(); // NIM e-MCF
            
            // Gestion des erreurs
            $table->string('error_code')->nullable();
            $table->text('error_desc')->nullable();
            
            // Timestamps
            $table->timestamp('submitted_at')->nullable(); // Date de soumission
            $table->timestamp('finalized_at')->nullable(); // Date de finalisation
            $table->timestamps();
            
            // Index
            $table->index('uid');
            $table->index('ifu');
            $table->index('status');
            $table->index('submitted_at');
            $table->index('finalized_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emecf_invoices');
    }
};
