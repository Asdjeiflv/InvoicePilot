<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Models\Client;
use App\Models\Quotation;
use App\Services\NumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuotationController extends Controller
{
    public function __construct(
        private NumberingService $numberingService
    ) {
        $this->authorizeResource(Quotation::class, 'quotation');
    }

    public function index(Request $request)
    {
        $query = Quotation::with(['client:id,code,company_name'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('quotation_no', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->client_id, function ($q, $clientId) {
                $q->where('client_id', $clientId);
            })
            ->latest('issue_date');

        $quotations = $request->per_page
            ? $query->paginate($request->per_page)->withQueryString()
            : $query->paginate(15)->withQueryString();

        return Inertia::render('Quotations/Index', [
            'quotations' => $quotations,
            'filters' => $request->only(['search', 'status', 'client_id', 'per_page']),
        ]);
    }

    public function create()
    {
        $clients = Client::select('id', 'code', 'company_name')
            ->orderBy('code')
            ->get();

        return Inertia::render('Quotations/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(StoreQuotationRequest $request)
    {
        $quotation = DB::transaction(function () use ($request) {
            // Generate quotation number
            $quotationNo = $this->numberingService->generateQuotationNumber();

            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $lineTax = $lineTotal * ($item['tax_rate'] / 100);
                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
            }

            // Create quotation
            $quotation = Quotation::create([
                'quotation_no' => $quotationNo,
                'client_id' => $request->client_id,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
                'status' => 'draft',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // Create items
            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];

                $quotation->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'line_total' => $lineTotal,
                ]);
            }

            return $quotation->fresh(['items', 'client']);
        });

        return redirect()->route('quotations.show', $quotation)
            ->with('success', '見積を作成しました');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['client', 'items', 'invoices']);

        return Inertia::render('Quotations/Show', [
            'quotation' => $quotation,
        ]);
    }

    public function edit(Quotation $quotation)
    {
        // Prevent editing of finalized quotations
        if (in_array($quotation->status, ['approved', 'rejected', 'expired'])) {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', '承認済み、却下済み、または期限切れの見積は編集できません');
        }

        $quotation->load('items');

        $clients = Client::select('id', 'code', 'company_name')
            ->orderBy('code')
            ->get();

        return Inertia::render('Quotations/Edit', [
            'quotation' => $quotation,
            'clients' => $clients,
        ]);
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation)
    {
        DB::transaction(function () use ($request, $quotation) {
            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $lineTax = $lineTotal * ($item['tax_rate'] / 100);
                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
            }

            // Update quotation
            $quotation->update([
                'client_id' => $request->client_id,
                'issue_date' => $request->issue_date,
                'expiry_date' => $request->expiry_date,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
                'notes' => $request->notes,
            ]);

            // Delete existing items and recreate
            $quotation->items()->delete();

            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];

                $quotation->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'line_total' => $lineTotal,
                ]);
            }
        });

        return redirect()->route('quotations.show', $quotation)
            ->with('success', '見積を更新しました');
    }

    public function destroy(Quotation $quotation)
    {
        // Prevent deletion of approved quotations with invoices
        if ($quotation->status === 'approved' && $quotation->invoices()->exists()) {
            return redirect()->route('quotations.index')
                ->with('error', '請求書が作成済みの見積は削除できません');
        }

        $quotation->delete();

        return redirect()->route('quotations.index')
            ->with('success', '見積を削除しました');
    }

    /**
     * Approve a quotation
     */
    public function approve(Quotation $quotation)
    {
        $this->authorize('update', $quotation);

        if ($quotation->status !== 'draft') {
            return back()->with('error', '下書きステータスの見積のみ承認できます');
        }

        $quotation->update(['status' => 'approved']);

        return back()->with('success', '見積を承認しました');
    }

    /**
     * Reject a quotation
     */
    public function reject(Quotation $quotation)
    {
        $this->authorize('update', $quotation);

        if (!in_array($quotation->status, ['draft', 'sent'])) {
            return back()->with('error', '下書きまたは送付済みステータスの見積のみ却下できます');
        }

        $quotation->update(['status' => 'rejected']);

        return back()->with('success', '見積を却下しました');
    }
}
