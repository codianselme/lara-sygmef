<?php

namespace Codianselme\LaraSygmef\Http\Controllers;

use Codianselme\LaraSygmef\Models\EmecfInvoice;
use Codianselme\LaraSygmef\Services\EmecfService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private EmecfService $emecfService
    ) {}

    /**
     * Dashboard principal
     */
    public function index()
    {
        try {
            // Statistiques
            $stats = [
                'total' => EmecfInvoice::count(),
                'pending' => EmecfInvoice::where('status', 'pending')->count(),
                'confirmed' => EmecfInvoice::where('status', 'confirmed')->count(),
                'cancelled' => EmecfInvoice::where('status', 'cancelled')->count(),
                'total_amount' => EmecfInvoice::where('status', 'confirmed')->sum('total'),
                'today' => EmecfInvoice::whereDate('created_at', today())->count(),
            ];

            // Factures récentes
            $recentInvoices = EmecfInvoice::with(['items', 'payments'])
                ->latest()
                ->take(10)
                ->get();

            // Statistiques par mois (6 derniers mois)
            $monthlyStats = EmecfInvoice::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
                ->where('created_at', '>=', now()->subMonths(6))
                ->where('status', 'confirmed')
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        } catch (\Exception $e) {
            // Mode démo si la base de données n'est pas disponible
            $stats = [
                'total' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'cancelled' => 0,
                'total_amount' => 0,
                'today' => 0,
            ];
            
            $recentInvoices = collect([]);
            $monthlyStats = collect([]);
        }

        return view('emecf::dashboard.index', compact('stats', 'recentInvoices', 'monthlyStats'));
    }

    /**
     * Liste des factures
     */
    public function invoices(Request $request)
    {
        try {
            $query = EmecfInvoice::with(['items', 'payments']);

            // Filtres
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('uid', 'like', "%{$search}%")
                      ->orWhere('ifu', 'like', "%{$search}%")
                      ->orWhere('client_name', 'like', "%{$search}%")
                      ->orWhere('operator_name', 'like', "%{$search}%");
                });
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $invoices = $query->latest()->paginate(15);
        } catch (\Exception $e) {
            // Mode démo
            $invoices = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        return view('emecf::dashboard.invoices', compact('invoices'));
    }

    /**
     * Afficher une facture
     */
    public function show($id)
    {
        $invoice = EmecfInvoice::with(['items', 'payments'])->findOrFail($id);

        return view('emecf::dashboard.show', compact('invoice'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        return view('emecf::dashboard.create');
    }

    /**
     * Créer une facture
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ifu' => 'required|string|size:13',
            'type' => 'required|in:FV,EV,FA,EA',
            'operator_name' => 'required|string|max:255',
            'client_name' => 'nullable|string|max:255',
            'client_contact' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.taxGroup' => 'required|in:A,B,C,D,E,F',
        ]);

        try {
            // Préparer les données pour l'API
            $invoiceData = [
                'ifu' => $validated['ifu'],
                'type' => $validated['type'],
                'operator' => ['name' => $validated['operator_name']],
                'items' => $validated['items'],
            ];

            if (!empty($validated['client_name'])) {
                $invoiceData['client'] = [
                    'name' => $validated['client_name'],
                    'contact' => $validated['client_contact'] ?? null,
                ];
            }

            // Calculer le total
            $total = collect($validated['items'])->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            });

            $invoiceData['payment'] = [
                ['name' => 'ESPECES', 'amount' => $total]
            ];

            // Soumettre à l'API
            $result = $this->emecfService->submitInvoice($invoiceData);

            if (!$result['success']) {
                return back()->with('error', $result['error'])->withInput();
            }

            // Tenter de sauvegarder en base de données
            try {
                DB::beginTransaction();

                $invoice = EmecfInvoice::create([
                    'uid' => $result['data']['uid'],
                    'ifu' => $validated['ifu'],
                    'type' => $validated['type'],
                    'operator_name' => $validated['operator_name'],
                    'client_name' => $validated['client_name'] ?? null,
                    'client_contact' => $validated['client_contact'] ?? null,
                    'ta' => $result['data']['ta'] ?? 0,
                    'tb' => $result['data']['tb'] ?? 0,
                    'tc' => $result['data']['tc'] ?? 0,
                    'td' => $result['data']['td'] ?? 0,
                    'taa' => $result['data']['taa'] ?? 0,
                    'tab' => $result['data']['tab'] ?? 0,
                    'tac' => $result['data']['tac'] ?? 0,
                    'tad' => $result['data']['tad'] ?? 0,
                    'tae' => $result['data']['tae'] ?? 0,
                    'taf' => $result['data']['taf'] ?? 0,
                    'hab' => $result['data']['hab'] ?? 0,
                    'had' => $result['data']['had'] ?? 0,
                    'vab' => $result['data']['vab'] ?? 0,
                    'vad' => $result['data']['vad'] ?? 0,
                    'total' => $result['data']['total'] ?? $total,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ]);

                // Créer les items
                foreach ($validated['items'] as $item) {
                    $invoice->items()->create($item);
                }

                DB::commit();

                return redirect()->route('emecf.dashboard.show', $invoice->id)
                    ->with('success', 'Facture créée avec succès !');
            } catch (\Exception $dbError) {
                // Mode démo ou Erreur DB : La base de données n'est pas disponible
                // Mais l'API a bien créé la facture, on veut donc l'afficher !
                DB::rollBack();
                
                // Créer une instance temporaire pour l'affichage
                $invoice = new EmecfInvoice();
                $invoice->forceFill([
                    'uid' => $result['data']['uid'],
                    'ifu' => $validated['ifu'],
                    'type' => $validated['type'],
                    'operator_name' => $validated['operator_name'],
                    'client_name' => $validated['client_name'] ?? null,
                    'client_contact' => $validated['client_contact'] ?? null,
                    'ta' => $result['data']['ta'] ?? 0,
                    'tb' => $result['data']['tb'] ?? 0,
                    'tc' => $result['data']['tc'] ?? 0,
                    'td' => $result['data']['td'] ?? 0,
                    'taa' => $result['data']['taa'] ?? 0,
                    'tab' => $result['data']['tab'] ?? 0,
                    'tac' => $result['data']['tac'] ?? 0,
                    'tad' => $result['data']['tad'] ?? 0,
                    'tae' => $result['data']['tae'] ?? 0,
                    'taf' => $result['data']['taf'] ?? 0,
                    'hab' => $result['data']['hab'] ?? 0,
                    'had' => $result['data']['had'] ?? 0,
                    'vab' => $result['data']['vab'] ?? 0,
                    'vad' => $result['data']['vad'] ?? 0,
                    'total' => $result['data']['total'] ?? $total,
                    'status' => 'pending',
                    'created_at' => now(),
                ]);
                
                // Ajouter les items manuellement à l'instance (relation non persistée)
                $invoice->setRelation('items', collect($validated['items'])->map(function($item) {
                    $invoiceItem = new \Codianselme\LaraSygmef\Models\EmecfInvoiceItem();
                    $invoiceItem->forceFill([
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'tax_group' => $item['taxGroup'], // Mapping correct
                    ]);
                    return $invoiceItem;
                }));

                return view('emecf::dashboard.show', compact('invoice'))
                    ->with('success', 'Facture créée sur l\'API e-MECeF ! (Note: Non sauvegardée localement - ' . $dbError->getMessage() . ')');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Confirmer une facture
     */
    public function confirm($id)
    {
        $invoice = EmecfInvoice::findOrFail($id);

        if ($invoice->status !== 'pending') {
            return back()->with('error', 'Cette facture ne peut plus être confirmée.');
        }

        $result = $this->emecfService->finalizeInvoice($invoice->uid, 'confirm');

        if (!$result['success']) {
            $invoice->update([
                'status' => 'error',
                'error_code' => $result['error_code'] ?? 'UNKNOWN',
                'error_desc' => $result['error'] ?? 'Erreur inconnue',
            ]);
            return back()->with('error', $result['error']);
        }

        $invoice->update([
            'status' => 'confirmed',
            'code_mec_ef_dgi' => $result['data']['codeMECeFDGI'] ?? null,
            'qr_code' => $result['data']['qrCode'] ?? null,
            'date_time' => $result['data']['dateTime'] ?? null,
            'counters' => $result['data']['counters'] ?? null,
            'nim' => $result['data']['nim'] ?? null,
            'finalized_at' => now(),
        ]);

        return back()->with('success', 'Facture confirmée avec succès ! QR Code généré.');
    }

    /**
     * Annuler une facture
     */
    public function cancel($id)
    {
        $invoice = EmecfInvoice::findOrFail($id);

        if ($invoice->status !== 'pending') {
            return back()->with('error', 'Cette facture ne peut plus être annulée.');
        }

        $result = $this->emecfService->finalizeInvoice($invoice->uid, 'cancel');

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        $invoice->update([
            'status' => 'cancelled',
            'finalized_at' => now(),
        ]);

        return back()->with('success', 'Facture annulée avec succès.');
    }
}
