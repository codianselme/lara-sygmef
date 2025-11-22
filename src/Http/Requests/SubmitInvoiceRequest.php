<?php

namespace Codianselme\LaraSygmef\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ifu' => 'required|string|size:13|regex:/^[0-9]{13}$/',
            'aib' => 'nullable|in:A,B',
            'type' => 'required|in:FV,EV,FA,EA',
            'reference' => 'required_if:type,FA,EA|string|size:24|regex:/^[A-Za-z0-9]{24}$/',
            
            // Validation des articles
            'items' => 'required|array|min:1|max:100',
            'items.*.code' => 'nullable|string|max:50',
            'items.*.name' => 'required|string|max:255',
            'items.*.price' => 'required|integer|min:0|max:999999999',
            'items.*.quantity' => 'required|numeric|min:0.001|max:999999.999',
            'items.*.taxGroup' => 'required|in:A,B,C,D,E,F',
            'items.*.taxSpecific' => 'nullable|integer|min:0|max:999999999',
            'items.*.originalPrice' => 'nullable|integer|min:0|max:999999999',
            'items.*.priceModification' => 'nullable|string|max:255',
            
            // Validation du client
            'client' => 'nullable|array',
            'client.ifu' => 'nullable|string|size:13|regex:/^[0-9]{13}$/',
            'client.name' => 'nullable|string|max:255',
            'client.contact' => 'nullable|string|max:255',
            'client.address' => 'nullable|string|max:500',
            
            // Validation de l'opérateur
            'operator' => 'required|array',
            'operator.id' => 'nullable|string|max:50',
            'operator.name' => 'required|string|max:255',
            
            // Validation des paiements
            'payment' => 'nullable|array|max:10',
            'payment.*.name' => 'required|in:ESPECES,VIREMENT,CARTEBANCAIRE,MOBILEMONEY,CHEQUES,CREDIT,AUTRE',
            'payment.*.amount' => 'required|integer|min:0|max:999999999',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ifu.required' => 'L\'IFU est obligatoire',
            'ifu.size' => 'L\'IFU doit contenir exactement 13 caractères',
            'ifu.regex' => 'L\'IFU doit contenir uniquement des chiffres',
            
            'aib.in' => 'Le type AIB doit être A ou B',
            
            'type.required' => 'Le type de facture est obligatoire',
            'type.in' => 'Le type de facture doit être FV, EV, FA ou EA',
            
            'reference.required_if' => 'La référence est obligatoire pour les factures d\'avoir',
            'reference.size' => 'La référence doit contenir exactement 24 caractères',
            'reference.regex' => 'La référence doit contenir uniquement des lettres et chiffres',
            
            'items.required' => 'Au moins un article est obligatoire',
            'items.min' => 'Au moins un article est obligatoire',
            'items.max' => 'Le nombre d\'articles ne peut pas dépasser 100',
            
            'items.*.name.required' => 'Le nom de l\'article est obligatoire',
            'items.*.name.max' => 'Le nom de l\'article ne peut pas dépasser 255 caractères',
            
            'items.*.price.required' => 'Le prix de l\'article est obligatoire',
            'items.*.price.integer' => 'Le prix doit être un entier',
            'items.*.price.min' => 'Le prix doit être supérieur ou égal à 0',
            'items.*.price.max' => 'Le prix ne peut pas dépasser 999 999 999',
            
            'items.*.quantity.required' => 'La quantité est obligatoire',
            'items.*.quantity.min' => 'La quantité doit être supérieure à 0',
            'items.*.quantity.max' => 'La quantité ne peut pas dépasser 999 999.999',
            
            'items.*.taxGroup.required' => 'Le groupe de taxation est obligatoire',
            'items.*.taxGroup.in' => 'Le groupe de taxation doit être A, B, C, D, E ou F',
            
            'operator.required' => 'Les informations de l\'opérateur sont obligatoires',
            'operator.name.required' => 'Le nom de l\'opérateur est obligatoire',
            'operator.name.max' => 'Le nom de l\'opérateur ne peut pas dépasser 255 caractères',
            
            'payment.max' => 'Le nombre de types de paiement ne peut pas dépasser 10',
            
            'payment.*.name.required' => 'Le type de paiement est obligatoire',
            'payment.*.name.in' => 'Le type de paiement doit être ESPECES, VIREMENT, CARTEBANCAIRE, MOBILEMONEY, CHEQUES, CREDIT ou AUTRE',
            
            'payment.*.amount.required' => 'Le montant du paiement est obligatoire',
            'payment.*.amount.integer' => 'Le montant du paiement doit être un entier',
            'payment.*.amount.min' => 'Le montant du paiement doit être supérieur ou égal à 0',
            
            'client.ifu.size' => 'L\'IFU du client doit contenir exactement 13 caractères',
            'client.ifu.regex' => 'L\'IFU du client doit contenir uniquement des chiffres',
            'client.name.max' => 'Le nom du client ne peut pas dépasser 255 caractères',
            'client.contact.max' => 'Le contact du client ne peut pas dépasser 255 caractères',
            'client.address.max' => 'L\'adresse du client ne peut pas dépasser 500 caractères',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validatePaymentAmounts($validator);
            $this->validateInvoiceType($validator);
            $this->validateTaxSpecificForGroups($validator);
        });
    }

    /**
     * Valider que les montants de paiement correspondent au total
     */
    protected function validatePaymentAmounts(Validator $validator): void
    {
        if (!$this->has('payment')) {
            return;
        }

        $totalPayments = array_sum(array_column($this->payment, 'amount'));
        
        // Calculer le total des articles
        $totalItems = 0;
        foreach ($this->items as $item) {
            $totalItems += $item['price'] * $item['quantity'];
        }

        if ($totalPayments !== $totalItems) {
            $validator->errors()->add('payment', 'Le total des paiements doit correspondre au montant total de la facture');
        }
    }

    /**
     * Valider la cohérence du type de facture
     */
    protected function validateInvoiceType(Validator $validator): void
    {
        $type = $this->input('type');
        $reference = $this->input('reference');
        $items = $this->input('items', []);

        // Les factures d'avoir doivent avoir une référence
        if (in_array($type, ['FA', 'EA']) && empty($reference)) {
            $validator->errors()->add('reference', 'La référence est obligatoire pour les factures d\'avoir');
        }

        // Les factures d'avoir ne peuvent pas avoir d'articles avec des quantités négatives
        // (Cette validation dépend des règles métier spécifiques)
        foreach ($items as $index => $item) {
            if (in_array($type, ['FA', 'EA']) && $item['quantity'] > 0) {
                $validator->errors()->add("items.{$index}.quantity", 'Les factures d\'avoir doivent avoir des quantités négatives');
            }
        }
    }

    /**
     * Valider que les groupes avec taxe spécifique sont valides
     */
    protected function validateTaxSpecificForGroups(Validator $validator): void
    {
        $items = $this->input('items', []);

        foreach ($items as $index => $item) {
            // Seuls les groupes A, E, F peuvent avoir une taxe spécifique
            if (!empty($item['taxSpecific']) && !in_array($item['taxGroup'], ['A', 'E', 'F'])) {
                $validator->errors()->add("items.{$index}.taxSpecific", 'La taxe spécifique n\'est autorisée que pour les groupes A, E et F');
            }
        }
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => 'Erreur de validation des données',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
