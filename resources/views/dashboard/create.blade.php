@extends('emecf::dashboard.layout')

@section('title', 'Créer une facture - e-MECeF')

@section('content')
<div class="header">
    <div class="header-title">
        <h2>Créer une Facture</h2>
        <p>Soumission directe à l'API e-MECeF</p>
    </div>
    <a href="{{ route('emecf.dashboard.invoices') }}" class="btn" style="background: var(--gray); color: var(--white);">
        <span>←</span>
        <span>Retour</span>
    </a>
</div>

@if(session('error'))
    <div class="alert alert-error">
        <span>❌</span>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-error">
        <span>⚠️</span>
        <div>
            <strong>Erreurs de validation :</strong>
            <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="content-area">
    <form method="POST" action="{{ route('emecf.dashboard.store') }}" x-data="invoiceForm()">
        @csrf
        
        <!-- Informations générales -->
        <div style="margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem; font-weight: 700; color: var(--dark);">Informations Générales</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">IFU *</label>
                    <input type="text" name="ifu" value="{{ old('ifu', '0202113169876') }}" required 
                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--gray-light); border-radius: 8px; font-size: 1rem;"
                           placeholder="1234567890123">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Type de Facture *</label>
                    <select name="type" required 
                            style="width: 100%; padding: 0.75rem; border: 2px solid var(--gray-light); border-radius: 8px; font-size: 1rem;">
                        <option value="FV" {{ old('type') == 'FV' ? 'selected' : '' }}>FV - Facture de vente</option>
                        <option value="FA" {{ old('type') == 'FA' ? 'selected' : '' }}>FA - Facture d'avoir</option>
                        <option value="EV" {{ old('type') == 'EV' ? 'selected' : '' }}>EV - Vente export</option>
                        <option value="EA" {{ old('type') == 'EA' ? 'selected' : '' }}>EA - Avoir export</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Opérateur *</label>
                    <input type="text" name="operator_name" value="{{ old('operator_name', 'JERIMO-YAMAH') }}" required 
                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--gray-light); border-radius: 8px; font-size: 1rem;"
                           placeholder="Nom de l'opérateur">
                </div>
            </div>
        </div>

        <!-- Informations Client -->
        <div style="margin-bottom: 2rem; padding: 1.5rem; background: var(--gray-light); border-radius: 12px;">
            <h3 style="margin-bottom: 1rem; font-weight: 700; color: var(--dark);">Informations Client (Optionnel)</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Nom du Client</label>
                    <input type="text" name="client_name" value="{{ old('client_name') }}" 
                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--white); border-radius: 8px; font-size: 1rem;"
                           placeholder="Nom du client">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Contact</label>
                    <input type="text" name="client_contact" value="{{ old('client_contact') }}" 
                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--white); border-radius: 8px; font-size: 1rem;"
                           placeholder="+229XXXXXXXX">
                </div>
            </div>
        </div>

        <!-- Articles -->
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="font-weight: 700; color: var(--dark);">Articles *</h3>
                <button type="button" @click="addItem" class="btn btn-sm" style="background: var(--success);">
                    <span>➕</span>
                    <span>Ajouter un article</span>
                </button>
            </div>
            
            <template x-for="(item, index) in items" :key="index">
                <div style="padding: 1.5rem; background: var(--gray-light); border-radius: 12px; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: between; align-items-center; margin-bottom: 1rem;">
                        <strong style="color: var(--gray);">Article <span x-text="index + 1"></span></strong>
                        <button type="button" @click="removeItem(index)" x-show="items.length > 1" 
                                style="background: var(--danger); color: white; padding: 0.5rem 1rem; border-radius: 6px; border: none; cursor: pointer;">
                            ❌ Retirer
                        </button>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Nom *</label>
                            <input type="text" :name="'items['+index+'][name]'" x-model="item.name" required 
                                   style="width: 100%; padding: 0.75rem; border: 2px solid var(--white); border-radius: 8px;"
                                   placeholder="Nom de l'article">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Prix *</label>
                            <input type="number" :name="'items['+index+'][price]'" x-model="item.price" required min="0" 
                                   style="width: 100%; padding: 0.75rem; border: 2px solid var(--white); border-radius: 8px;"
                                   placeholder="0">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Quantité *</label>
                            <input type="number" :name="'items['+index+'][quantity]'" x-model="item.quantity" required min="0.01" step="0.01" 
                                   style="width: 100%; padding: 0.75rem; border: 2px solid var(--white); border-radius: 8px;"
                                   placeholder="1">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Groupe Taxe *</label>
                            <select :name="'items['+index+'][taxGroup]'" x-model="item.taxGroup" required 
                                    style="width: 100%; padding: 0.75rem; border: 2px solid var(--white); border-radius: 8px;">
                                <option value="A">A - 0%</option>
                                <option value="B">B - 18%</option>
                                <option value="C">C - 0%</option>
                                <option value="D">D - 18%</option>
                                <option value="E">E - 0%</option>
                                <option value="F">F - 0%</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-top: 0.75rem; padding: 0.75rem; background: white; border-radius: 8px; font-weight: 600;">
                        Total article : <span x-text="(item.price * item.quantity).toLocaleString()"></span> FCFA
                    </div>
                </div>
            </template>
        </div>

        <!-- Total Général -->
        <div style="padding: 1.5rem; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 12px; margin-bottom: 2rem;">
            <div style="color: white; font-size: 1.5rem; font-weight: 700; text-align: center;">
                Total Général : <span x-text="totalAmount.toLocaleString()"></span> FCFA
            </div>
        </div>

        <!-- Boutons -->
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <a href="{{ route('emecf.dashboard.index') }}" class="btn" style="background: var(--gray); color: var(--white);">
                Annuler
            </a>
            <button type="submit" class="btn btn-primary">
                <span>💾</span>
                <span>Créer la Facture</span>
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function invoiceForm() {
        return {
            items: [
                { name: '', price: 0, quantity: 1, taxGroup: 'B' }
            ],
            
            get totalAmount() {
                return this.items.reduce((sum, item) => {
                    return sum + (parseFloat(item.price) || 0) * (parseFloat(item.quantity) || 0);
                }, 0);
            },
            
            addItem() {
                this.items.push({ name: '', price: 0, quantity: 1, taxGroup: 'B' });
            },
            
            removeItem(index) {
                if (this.items.length > 1) {
                    this.items.splice(index, 1);
                }
            }
        }
    }
</script>
@endsection
