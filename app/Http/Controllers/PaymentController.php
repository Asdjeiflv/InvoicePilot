<?php

namespace App\Http\Controllers;

use App\Actions\Invoices\RecalculateInvoiceBalanceAction;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function __construct(
        private RecalculateInvoiceBalanceAction $recalculateBalanceAction
    ) {
        $this->authorizeResource(Payment::class, 'payment');
    }

    public function index(Request $request)
    {
        $query = Payment::with(['invoice:id,invoice_no,client_id', 'invoice.client:id,code,company_name'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('invoice', function ($q) use ($search) {
                            $q->where('invoice_no', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->method, function ($q, $method) {
                $q->where('method', $method);
            })
            ->when($request->invoice_id, function ($q, $invoiceId) {
                $q->where('invoice_id', $invoiceId);
            })
            ->latest('payment_date');

        $payments = $request->per_page
            ? $query->paginate($request->per_page)->withQueryString()
            : $query->paginate(15)->withQueryString();

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'method', 'invoice_id', 'per_page']),
        ]);
    }

    public function create(Request $request)
    {
        $invoice = null;
        if ($request->invoice_id) {
            $invoice = Invoice::with('client')->findOrFail($request->invoice_id);
        }

        $invoices = Invoice::with('client:id,code,company_name')
            ->whereIn('status', ['issued', 'partial_paid', 'overdue'])
            ->where('balance_due', '>', 0)
            ->orderBy('due_date')
            ->get();

        return Inertia::render('Payments/Create', [
            'invoice' => $invoice,
            'invoices' => $invoices,
        ]);
    }

    public function store(StorePaymentRequest $request)
    {
        $payment = DB::transaction(function () use ($request) {
            $invoice = Invoice::findOrFail($request->invoice_id);

            // Create payment
            $payment = Payment::create([
                'invoice_id' => $request->invoice_id,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'method' => $request->method,
                'reference_no' => $request->reference_no,
                'note' => $request->note,
                'recorded_by' => auth()->id(),
            ]);

            // Recalculate invoice balance
            $this->recalculateBalanceAction->execute($invoice);

            return $payment->fresh('invoice');
        });

        return redirect()->route('invoices.show', $payment->invoice_id)
            ->with('success', '入金を記録しました');
    }

    public function show(Payment $payment)
    {
        $payment->load(['invoice.client', 'recordedBy:id,name']);

        return Inertia::render('Payments/Show', [
            'payment' => $payment,
        ]);
    }

    public function edit(Payment $payment)
    {
        $payment->load('invoice.client');

        return Inertia::render('Payments/Edit', [
            'payment' => $payment,
        ]);
    }

    public function update(StorePaymentRequest $request, Payment $payment)
    {
        DB::transaction(function () use ($request, $payment) {
            $payment->update([
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'method' => $request->method,
                'reference_no' => $request->reference_no,
                'note' => $request->note,
            ]);

            // Recalculate invoice balance
            $this->recalculateBalanceAction->execute($payment->invoice);
        });

        return redirect()->route('invoices.show', $payment->invoice_id)
            ->with('success', '入金情報を更新しました');
    }

    public function destroy(Payment $payment)
    {
        $invoiceId = $payment->invoice_id;

        DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            $payment->delete();

            // Recalculate invoice balance after deletion
            $this->recalculateBalanceAction->execute($invoice);
        });

        return redirect()->route('invoices.show', $invoiceId)
            ->with('success', '入金記録を削除しました');
    }
}
