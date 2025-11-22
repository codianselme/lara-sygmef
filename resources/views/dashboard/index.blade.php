@extends('emecf::dashboard.layout')

@section('title', 'Tableau de bord - e-MECeF')

@section('content')
<div class="header">
    <div class="header-title">
        <h2>Tableau de bord</h2>
        <p>Vue d'ensemble de vos opérations e-MECeF</p>
    </div>
    <a href="{{ route('emecf.dashboard.create') }}" class="btn btn-primary">
        <span>➕</span>
        <span>Nouvelle facture</span>
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

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Factures</span>
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                📊
            </div>
        </div>
        <div class="stat-value">{{ number_format($stats['total']) }}</div>
        <div class="stat-change">{{ $stats['today'] }} aujourd'hui</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">En Attente</span>
            <div class="stat-icon" style="background: #fef3c7; color: #92400e;">
                ⏳
            </div>
        </div>
        <div class="stat-value">{{ number_format($stats['pending']) }}</div>
        <div class="stat-change" style="color: #f59e0b;">À confirmer</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Confirmées</span>
            <div class="stat-icon" style="background: #d1fae5; color: #065f46;">
                ✅
            </div>
        </div>
        <div class="stat-value">{{ number_format($stats['confirmed']) }}</div>
        <div class="stat-change">Avec QR code</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Montant Total</span>
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c); color: white;">
                💰
            </div>
        </div>
        <div class="stat-value">{{ number_format($stats['total_amount']) }}</div>
        <div class="stat-change">FCFA</div>
    </div>
</div>

<!-- Chart Section -->
<div class="content-area" style="margin-bottom: 2rem;">
    <h3 style="margin-bottom: 1.5rem; font-weight: 700;">Évolution des factures (6 derniers mois)</h3>
    <canvas id="invoicesChart" height="80"></canvas>
</div>

<!-- Recent Invoices -->
<div class="content-area">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="font-weight: 700;">Factures récentes</h3>
        <a href="{{ route('emecf.dashboard.invoices') }}" class="btn btn-sm btn-primary">Voir tout</a>
    </div>

    @if($recentInvoices->count() > 0)
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
                    @foreach($recentInvoices as $invoice)
                        <tr>
                            <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <code style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                                    {{ Str::limit($invoice->uid, 20) }}
                                </code>
                            </td>
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
                                <a href="{{ route('emecf.dashboard.show', $invoice->id) }}" style="color: var(--primary); text-decoration: none; font-weight: 600;">
                                    Détails →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="text-align: center; padding: 3rem; color: var(--gray);">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📄</div>
            <p>Aucune facture pour le moment</p>
            <a href="{{ route('emecf.dashboard.create') }}" class="btn btn-primary" style="margin-top: 1rem;">Créer votre première facture</a>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    // Chart configuration
    const ctx = document.getElementById('invoicesChart').getContext('2d');
    const monthlyData = @json($monthlyStats);
    
    const labels = monthlyData.map(item => {
        const [year, month] = item.month.split('-');
        const date = new Date(year, month - 1);
        return date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
    });
    
    const counts = monthlyData.map(item => item.count);
    const totals = monthlyData.map(item => item.total);
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Nombre de factures',
                    data: counts,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Montant total (FCFA)',
                    data: totals,
                    borderColor: '#ec4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                },
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Nombre de factures'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Montant (FCFA)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                },
            }
        }
    });
</script>
@endsection
