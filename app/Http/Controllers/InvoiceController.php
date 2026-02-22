<?php

namespace App\Http\Controllers;

use App\Actions\Invoices\CreateInvoiceFromQuotationAction;
use App\Exceptions\StaleObjectException;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\NumberingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private NumberingService $numberingService,
        private CreateInvoiceFromQuotationAction $createFromQuotationAction
    ) {
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    public function index(Request $request): InertiaResponse
    {
        $query = Invoice::with(['client:id,code,company_name'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice_no', 'like', "%{$search}%")
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
            ->when($request->overdue_only, function ($q) {
                $q->where('status', 'overdue');
            })
            ->latest('issue_date');

        $invoices = $request->per_page
            ? $query->paginate($request->per_page)->withQueryString()
            : $query->paginate(15)->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status', 'client_id', 'overdue_only', 'per_page']),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $clients = Client::select('id', 'code', 'company_name')
            ->orderBy('code')
            ->get();

        $quotation = null;
        if ($request->quotation_id) {
            $quotation = Quotation::with('items')->findOrFail($request->quotation_id);
        }

        return Inertia::render('Invoices/Create', [
            'clients' => $clients,
            'quotation' => $quotation,
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $invoice = DB::transaction(function () use ($request) {
            // Generate invoice number
            $invoiceNo = $this->numberingService->generateInvoiceNumber();

            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $lineTax = $lineTotal * ($item['tax_rate'] / 100);
                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
            }

            $total = $subtotal + $taxTotal;

            // Create invoice
            $invoice = Invoice::create([
                'invoice_no' => $invoiceNo,
                'client_id' => $request->client_id,
                'quotation_id' => $request->quotation_id ?? null,
                'issue_date' => $request->issue_date,
                'due_date' => $request->due_date,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'paid_amount' => 0,
                'balance_due' => $total,
                'status' => 'draft',
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // Create items
            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];

                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'line_total' => $lineTotal,
                ]);
            }

            return $invoice->fresh(['items', 'client']);
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', '請求書を作成しました');
    }

    public function show(Invoice $invoice): InertiaResponse
    {
        $invoice->load(['client', 'items', 'payments', 'quotation', 'reminders']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice): InertiaResponse|RedirectResponse
    {
        // Prevent editing of paid or canceled invoices
        if (in_array($invoice->status, ['paid', 'canceled'])) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', '支払済みまたはキャンセル済みの請求書は編集できません');
        }

        $invoice->load('items');

        $clients = Client::select('id', 'code', 'company_name')
            ->orderBy('code')
            ->get();

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'clients' => $clients,
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $invoice) {
                // Check optimistic lock version
                $invoice->checkVersion($request->input('version'));

                // Calculate totals
                $subtotal = 0;
                $taxTotal = 0;

                foreach ($request->items as $item) {
                    $lineTotal = $item['quantity'] * $item['unit_price'];
                    $lineTax = $lineTotal * ($item['tax_rate'] / 100);
                    $subtotal += $lineTotal;
                    $taxTotal += $lineTax;
                }

                $total = $subtotal + $taxTotal;

                // Update invoice
                $invoice->update([
                    'client_id' => $request->client_id,
                    'issue_date' => $request->issue_date,
                    'due_date' => $request->due_date,
                    'subtotal' => $subtotal,
                    'tax_total' => $taxTotal,
                    'total' => $total,
                    'balance_due' => $total - $invoice->paid_amount,
                    'notes' => $request->notes,
                ]);

                // Delete existing items and recreate
                $invoice->items()->delete();

                foreach ($request->items as $item) {
                    $lineTotal = $item['quantity'] * $item['unit_price'];

                    $invoice->items()->create([
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'tax_rate' => $item['tax_rate'],
                        'line_total' => $lineTotal,
                    ]);
                }
            });

            return redirect()->route('invoices.show', $invoice)
                ->with('success', '請求書を更新しました');
        } catch (StaleObjectException $e) {
            return back()
                ->withInput()
                ->with('error', '別のユーザーによって請求書が更新されています。ページを再読み込みして最新の情報を確認してください。');
        }
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        // Prevent deletion of invoices with payments
        if ($invoice->payments()->exists()) {
            return redirect()->route('invoices.index')
                ->with('error', '入金記録がある請求書は削除できません');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', '請求書を削除しました');
    }

    /**
     * Issue an invoice (change status from draft to issued)
     */
    public function issue(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return back()->with('error', '下書きステータスの請求書のみ発行できます');
        }

        $invoice->update(['status' => 'issued']);

        return back()->with('success', '請求書を発行しました');
    }

    /**
     * Cancel an invoice
     */
    public function cancel(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if ($invoice->payments()->exists()) {
            return back()->with('error', '入金記録がある請求書はキャンセルできません');
        }

        $invoice->update(['status' => 'canceled']);

        return back()->with('success', '請求書をキャンセルしました');
    }

    /**
     * Create invoice from quotation
     */
    public function createFromQuotation(Request $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $quotation = Quotation::findOrFail($request->quotation_id);

        try {
            $invoice = $this->createFromQuotationAction->execute($quotation, [
                'issue_date' => $request->issue_date ?? now()->toDateString(),
                'due_date' => $request->due_date ?? now()->addDays(30)->toDateString(),
            ]);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', '見積から請求書を作成しました');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
