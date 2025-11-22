@extends('emecf::dashboard.layout')

@section('title', 'Détails Facture - e-MECeF')

@section('content')
<div class="header">
    <div class="header-title">
        <h2>Facture #{{ $invoice->id }}</h2>
        <p>UID: {{ $invoice->uid }}</p>
    </div>
    <a href="{{ route('emecf.dashboard.invoices') }}" class="btn" style="background: var(--gray); color: var(--white);">
        <span>←</span>
        <span>Retour</span>
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <span>✅</span>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error">
        <span>❌</span>
        <span>{{ session('error') }}</span>
    </div>
@endif

<!-- Statut de la Facture -->
<div class="content-area" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="font-weight: 700; margin-bottom: 0.5rem;">Statut</h3>
            @if($invoice->status === 'pending')
                <span class="badge badge-pending" style="font-size: 1rem; padding: 0.5rem 1rem;">⏳ En attente de confirmation</span>
            @elseif($invoice->status === 'confirmed')
                <span class="badge badge-confirmed" style="font-size: 1rem; padding: 0.5rem 1rem;">✅ Confirmée</span>
            @else
                <span class="badge badge-cancelled" style="font-size: 1rem; padding: 0.5rem 1rem;">❌ Annulée</span>
            @endif
        </div>
        
        @if($invoice->status === 'pending')
            <div style="display: flex; gap: 1rem;">
                <form method="POST" action="{{ route('emecf.dashboard.confirm', $invoice->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <span>✅</span>
                        <span>Confirmer</span>
                    </button>
                </form>
                
                <form method="POST" action="{{ route('emecf.dashboard.cancel', $invoice->id) }}" 
                      onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette facture ?')">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <span>❌</span>
                        <span>Annuler</span>
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

<!-- QR Code (si confirmée) -->
@if($invoice->status === 'confirmed' && $invoice->qr_code)
    <div class="content-area" style="margin-bottom: 2rem; text-align: center;">
        <h3 style="font-weight: 700; margin-bottom: 1rem;">QR Code</h3>
        <div style="display: inline-block; padding: 2rem; background: white; border-radius: 12px; box-shadow: var(--shadow);">
            <div id="qrcode" style="margin-bottom: 1rem;"></div>
            <p style="font-family: monospace; font-size: 0.875rem; color: var(--gray);">{{ $invoice->qr_code }}</p>
        </div>
        
        @if($invoice->code_mec_ef_dgi)
            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--gray-light); border-radius: 8px; display: inline-block;">
                <strong>Code MECeF/DGI:</strong> 
                <code style="background: white; padding: 0.5rem; border-radius: 4px; margin-left: 0.5rem;">{{ $invoice->code_mec_ef_dgi }}</code>
            </div>
        @endif
    </div>
@endif

<!-- Détails de la Facture -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Informations générales -->
    <div class="content-area">
        <h3 style="font-weight: 700; margin-bottom: 1rem;">Informations Générales</h3>
        <table style="width: 100%;">
            <tr style="border-bottom: 1px solid var(--gray-light);">
                <td style="padding: 0.75rem 0; font-weight: 600;">IFU:</td>
                <td>{{ $invoice->ifu }}</td>
            </tr>
            <tr style="border-bottom: 1px solid var(--gray-light);">
                <td style="padding: 0.75rem 0; font-weight: 600;">Type:</td>
                <td>{{ $invoice->type }}</td>
            </tr>
            <tr style="border-bottom: 1px solid var(--gray-light);">
                <td style="padding: 0.75rem 0; font-weight: 600;">Opérateur:</td>
                <td>{{ $invoice->operator_name }}</td>
            </tr>
            <tr style="border-bottom: 1px solid var(--gray-light);">
                <td style="padding: 0.75rem 0; font-weight: 600;">Date création:</td>
                <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @if($invoice->finalized_at)
                <tr>
                    <td style="padding: 0.75rem 0; font-weight: 600;">Date finalisation:</td>
                    <td>{{ $invoice->finalized_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endif
        </table>
    </div>
    
    <!-- Client -->
    <div class="content-area">
        <h3 style="font-weight: 700; margin-bottom: 1rem;">Client</h3>
        <table style="width: 100%;">
            <tr style="border-bottom: 1px solid var(--gray-light);">
                <td style="padding: 0.75rem 0; font-weight: 600;">Nom:</td>
                <td>{{ $invoice->client_name ?: 'N/A' }}</td>
            </tr>
            <tr style="border-bottom: 1px solid var(--gray-light);">
                <td style="padding: 0.75rem 0; font-weight: 600;">Contact:</td>
                <td>{{ $invoice->client_contact ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td style="padding: 0.75rem 0; font-weight: 600;">IFU Client:</td>
                <td>{{ $invoice->client_ifu ?: 'N/A' }}</td>
            </tr>
        </table>
    </div>
</div>

<!-- Montants -->
<div class="content-area" style="margin-bottom: 2rem;">
    <h3 style="font-weight: 700; margin-bottom: 1rem;">Montants</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <div style="padding: 1rem; background: var(--gray-light); border-radius: 8px;">
            <div style="font-size: 0.875rem; color: var(--gray); margin-bottom: 0.25rem;">Montant HT (Groupe B)</div>
            <div style="font-size: 1.5rem; font-weight: 700;">{{ number_format($invoice->hab) }} FCFA</div>
        </div>
        
        <div style="padding: 1rem; background: var(--gray-light); border-radius: 8px;">
            <div style="font-size: 0.875rem; color: var(--gray); margin-bottom: 0.25rem;">TVA (18%)</div>
            <div style="font-size: 1.5rem; font-weight: 700;">{{ number_format($invoice->vab) }} FCFA</div>
        </div>
        
        <div style="padding: 1rem; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 8px; color: white;">
            <div style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 0.25rem;">Total</div>
            <div style="font-size: 1.75rem; font-weight: 700;">{{ number_format($invoice->total) }} FCFA</div>
        </div>
    </div>
</div>

<!-- Articles -->
@if($invoice->items && $invoice->items->count() > 0)
    <div class="content-area">
        <h3 style="font-weight: 700; margin-bottom: 1rem;">Articles</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Article</th>
                        <th>Prix Unitaire</th>
                        <th>Quantité</th>
                        <th>Groupe Taxe</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ number_format($item->price) }} FCFA</td>
                            <td>{{ $item->quantity }}</td>
                            <td><span class="badge" style="background: var(--gray-light); color: var(--dark);">{{ $item->taxGroup }}</span></td>
                            <td style="font-weight: 600;">{{ number_format($item->price * $item->quantity) }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

@section('scripts')
@if($invoice->status === 'confirmed' && $invoice->qr_code)
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "{{ $invoice->qr_code }}",
            width: 256,
            height: 256,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    </script>
@endif
@endsection
