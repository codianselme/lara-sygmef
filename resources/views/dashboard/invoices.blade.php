@extends('emecf::dashboard.layout')

@section('title', 'Factures - e-MECeF')

@section('content')
<div class="header">
    <div class="header-title">
        <h2>Gestion des Factures</h2>
        <p>{{ $invoices->total() }} facture(s) au total</p>
    </div>
    <a href="{{ route('emecf.dashboard.create') }}" class="btn btn-primary">
        <span>➕</span>
        <span>Nouvelle facture</span>
    </a>
</div>

<!-- Filters -->
<div class="content-area" style="margin-bottom: 2rem;">
    <form method="GET" action="{{ route('emecf.dashboard.invoices') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Recherche</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="UID, IFU, Client..." style="width: 100%; padding: 0.75rem; border: 2px solid var(--gray-light); border-radius: 8px;">
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Statut</label>
            <select name="status" style="width: 100%; padding: 0.75rem; border: 2px solid var(--gray-light); border-radius: 8px;">
                <option value="">Tous</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmée</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
            </select>
        </div>
        
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Date début</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" style="width: 100%; padding: 0.75rem; border: 2px solid var(--gray-light); border-radius: 8px;">
        </div>
        
        <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
            <button type="submit" class="btn btn-primary" style="width: 100%;">Filtrer</button>
        </div>
    </form>
</div>

<!-- Table -->
<div class="content-area">
    @if($invoices->count() > 0)
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>UID</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                            <td><code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">{{ Str::limit($invoice->uid, 20) }}</code></td>
                            <td>{{ $invoice->client_name ?: 'N/A' }}</td>
                            <td style="font-weight: 600;">{{ number_format($invoice->total) }} FCFA</td>
                            <td>
                                @if($invoice->status === 'pending')
                                    <span class="badge badge-pending">En attente</span>
                                @elseif($invoice->status === 'confirmed')
                                    <span class="badge badge-confirmed">Confirmée</span>
                                @else
                                    <span class="badge badge-cancelled">Annulée</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('emecf.dashboard.show', $invoice->id) }}" class="btn btn-sm btn-primary">Détails</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 1.5rem;">
            {{ $invoices->links() }}
        </div>
    @else
        <div style="text-align: center; padding: 3rem; color: var(--gray);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
            <p>Aucune facture trouvée</p>
        </div>
    @endif
</div>
@endsection
