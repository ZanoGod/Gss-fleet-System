<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Customer;
use App\Models\DailyBooking;
use App\Models\FinanceEntry;
use App\Models\FinanceInvoice;
use App\Models\FinancePayment;
use App\Models\InsurancePolicy;
use App\Models\MonthlyCar;
use App\Models\MonthlyContract;
use App\Models\VehicleMaintenance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FinanceController extends Controller
{
    private function filters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', Rule::in(['income', 'expense'])],
            'category' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['posted', 'draft'])],
            'currency' => ['nullable', 'string', 'size:3'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    private function entriesQuery(array $filters): Builder
    {
        return FinanceEntry::query()->with(['customer:id,company_name,guest_name', 'invoice:id,invoice_number'])
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['currency'] ?? null, fn ($q, $v) => $q->where('currency', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('entry_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('entry_date', '<=', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($s) => $s->where('description', 'like', "%{$v}%")->orWhere('reference', 'like', "%{$v}%")->orWhere('category', 'like', "%{$v}%")));
    }

    public function dashboard(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->entriesQuery($filters);
        $aggregate = (clone $query)->reorder()->selectRaw('currency, type, SUM(amount) AS total')->where('status', 'posted')->groupBy('currency', 'type')->get();
        $totals = $aggregate->groupBy('currency')->map(fn ($rows, $currency) => [
            'currency' => $currency,
            'income' => number_format((float) $rows->firstWhere('type', 'income')?->total, 2, '.', ''),
            'expenses' => number_format((float) $rows->firstWhere('type', 'expense')?->total, 2, '.', ''),
            'net' => number_format((float) $rows->firstWhere('type', 'income')?->total - (float) $rows->firstWhere('type', 'expense')?->total, 2, '.', ''),
        ])->values();
        $month = now()->startOfMonth();
        $monthTotals = FinanceEntry::query()->where('status', 'posted')->whereBetween('entry_date', [$month, now()->endOfMonth()])
            ->when($filters['currency'] ?? null, fn ($q, $v) => $q->where('currency', $v))
            ->selectRaw('currency, type, SUM(amount) AS total')->groupBy('currency', 'type')->get();
        $invoiceQuery = FinanceInvoice::query()->withSum('payments', 'amount')->whereNotIn('status', ['draft', 'void']);
        $receivables = (clone $invoiceQuery)->get()->groupBy('currency')->map(fn ($rows, $currency) => [
            'currency' => $currency,
            'outstanding' => number_format($rows->sum(fn ($i) => max(0, (float) $i->total - (float) $i->payments_sum_amount)), 2, '.', ''),
        ])->values();

        return response()->json(['data' => [
            'totals' => $totals,
            'month_totals' => $monthTotals->groupBy('currency')->map(fn ($rows, $currency) => ['currency' => $currency, 'income' => number_format((float) $rows->firstWhere('type', 'income')?->total, 2, '.', ''), 'expenses' => number_format((float) $rows->firstWhere('type', 'expense')?->total, 2, '.', '')])->values(),
            'receivables' => $receivables,
            'invoice_count' => (clone $invoiceQuery)->count(),
            'overdue_count' => FinanceInvoice::query()->where('status', 'sent')->whereDate('due_date', '<', today())->withSum('payments', 'amount')->get()->filter(fn ($invoice) => (float) $invoice->payments_sum_amount < (float) $invoice->total)->count(),
            'recent_entries' => (clone $query)->latest('entry_date')->latest('id')->limit(8)->get(),
        ]]);
    }

    public function options()
    {
        return response()->json(['data' => [
            'customers' => Customer::query()->orderBy('company_name')->get(['id', 'company_name', 'guest_name']),
            'bookings' => DailyBooking::query()->whereNotIn('status', ['Cancelled'])->latest('start_date')->limit(300)->get(['id', 'guest_company_name', 'start_date', 'end_date']),
            'contracts' => MonthlyContract::query()->whereNotIn('status', ['Cancelled'])->latest('start_date')->limit(300)->get(['id', 'contract_no', 'customer_id', 'customer_name', 'monthly_rate', 'rental_currency']),
            'vehicles' => Car::query()->get(['id', 'plate_no', 'model_name'])->map(fn ($v) => [...$v->toArray(), 'fleet' => 'daily'])
                ->concat(MonthlyCar::query()->get(['id', 'plate_no', 'model_name'])->map(fn ($v) => [...$v->toArray(), 'fleet' => 'monthly'])),
            'maintenance' => VehicleMaintenance::query()->whereNotNull('actual_cost')->latest('scheduled_date')->limit(300)->get(['id', 'title', 'actual_cost', 'currency', 'car_id', 'monthly_car_id']),
            'insurance' => InsurancePolicy::query()->latest('start_date')->limit(300)->get(['id', 'policy_number', 'premium_amount', 'currency', 'car_id', 'monthly_car_id']),
            'income_categories' => ['Daily rental', 'Monthly contract', 'Insurance recovery', 'Other income'],
            'expense_categories' => ['Fuel', 'Maintenance', 'Insurance', 'Licensing', 'Driver costs', 'Tolls and fines', 'Other expense'],
            'currencies' => ['MMK', 'USD'],
            'payment_methods' => ['Cash', 'Bank transfer', 'Card', 'Mobile wallet', 'Other'],
        ]]);
    }

    public function entries(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->entriesQuery($filters)->orderByDesc('entry_date')->orderByDesc('id')->paginate($filters['per_page'] ?? 10)->withQueryString();
        return response()->json(['data' => $rows->items(), 'meta' => ['total' => $rows->total(), 'current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage()]]);
    }

    public function storeEntry(Request $request)
    {
        abort_unless(request()->user()?->hasPermission('finance.manage'), 403);
        $data = $this->entryData($request);
        $entry = DB::transaction(function () use ($data, $request): FinanceEntry {
            $entry = FinanceEntry::create([...$data, 'created_by' => $request->user()->id]);
            if (($data['create_invoice'] ?? false) && $entry->type === 'income') $this->createInvoiceForEntry($entry, $data, $request->user()->id);
            return $entry;
        });
        return response()->json(['data' => $entry->load(['customer', 'invoice'])], 201);
    }

    public function updateEntry(Request $request, FinanceEntry $entry)
    {
        abort_unless(request()->user()?->hasPermission('finance.manage'), 403);
        $data = $this->entryData($request, true);
        if ($entry->invoice && $entry->invoice->payments()->exists()) throw ValidationException::withMessages(['entry' => 'Income with recorded invoice payments cannot be changed.']);
        if ($entry->invoice?->status === 'void') throw ValidationException::withMessages(['entry' => 'Voided invoice income cannot be changed.']);
        $entry->update(collect($data)->except(['create_invoice', 'invoice_due_date'])->all());
        if ($entry->invoice) $entry->invoice->update(['description' => $entry->description, 'customer_id' => $entry->customer_id, 'subtotal' => $entry->amount, 'total' => $entry->amount, 'currency' => $entry->currency, 'status' => $entry->status === 'posted' ? 'sent' : 'draft']);
        elseif (($data['create_invoice'] ?? false) && $entry->type === 'income') $this->createInvoiceForEntry($entry, $data, $request->user()->id);
        return response()->json(['data' => $entry->fresh()->load(['customer', 'invoice'])]);
    }

    public function destroyEntry(FinanceEntry $entry)
    {
        abort_unless(request()->user()?->hasPermission('finance.manage'), 403);
        if ($entry->invoice) throw ValidationException::withMessages(['entry' => 'Delete or void its invoice before deleting this income record.']);
        $entry->delete();
        return response()->noContent();
    }

    private function entryData(Request $request, bool $updating = false): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['income', 'expense'])], 'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:180'], 'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'currency' => ['required', 'string', 'size:3', Rule::in(['MMK', 'USD'])], 'entry_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(['posted', 'draft'])], 'payment_method' => ['nullable', 'string', 'max:40'], 'reference' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'], 'daily_booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
            'monthly_contract_id' => ['nullable', 'integer', 'exists:monthly_contracts,id'], 'vehicle_fleet' => ['nullable', Rule::in(['daily', 'monthly'])],
            'vehicle_id' => ['nullable', 'integer', 'min:1'], 'maintenance_id' => ['nullable', 'integer', 'exists:vehicle_maintenances,id'],
            'insurance_policy_id' => ['nullable', 'integer', 'exists:insurance_policies,id'], 'notes' => ['nullable', 'string', 'max:5000'],
            'create_invoice' => ['sometimes', 'boolean'], 'invoice_due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:entry_date'],
        ]);
        if (! empty($data['vehicle_id']) && (($data['vehicle_fleet'] ?? null) === 'daily' ? ! Car::whereKey($data['vehicle_id'])->exists() : ! MonthlyCar::whereKey($data['vehicle_id'])->exists())) {
            throw ValidationException::withMessages(['vehicle_id' => 'Choose a vehicle in the selected fleet.']);
        }
        if (($data['create_invoice'] ?? false) && $data['type'] !== 'income') throw ValidationException::withMessages(['create_invoice' => 'Only income can be invoiced.']);
        if ($updating) unset($data['type']);
        return $data;
    }

    private function createInvoiceForEntry(FinanceEntry $entry, array $data, int $userId): FinanceInvoice
    {
        $date = $entry->entry_date->format('Y-m-d');
        return FinanceInvoice::create([
            'invoice_number' => 'INV-'.now()->format('ym').'-'.Str::upper(Str::random(6)), 'income_entry_id' => $entry->id,
            'customer_id' => $entry->customer_id, 'daily_booking_id' => $entry->daily_booking_id, 'monthly_contract_id' => $entry->monthly_contract_id,
            'description' => $entry->description, 'issue_date' => $date, 'due_date' => $data['invoice_due_date'] ?? now()->addDays(14)->toDateString(),
            'subtotal' => $entry->amount, 'tax_amount' => 0, 'total' => $entry->amount, 'currency' => $entry->currency,
            'status' => $entry->status === 'posted' ? 'sent' : 'draft', 'notes' => $entry->notes, 'created_by' => $userId,
        ]);
    }

    public function invoices(Request $request)
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:200'], 'status' => ['nullable', Rule::in(['draft', 'sent', 'void'])], 'currency' => ['nullable', 'string', 'size:3'], 'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = FinanceInvoice::query()->with(['customer:id,company_name,guest_name'])->withSum('payments', 'amount')
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['currency'] ?? null, fn ($q, $v) => $q->where('currency', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('issue_date', '>=', $v))->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('issue_date', '<=', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($s) => $s->where('invoice_number', 'like', "%{$v}%")->orWhere('description', 'like', "%{$v}%")));
        $rows = $query->orderByDesc('issue_date')->orderByDesc('id')->paginate($filters['per_page'] ?? 10)->withQueryString();
        return response()->json(['data' => $rows->items(), 'meta' => ['total' => $rows->total(), 'current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage()]]);
    }

    public function storeInvoice(Request $request)
    {
        abort_unless(request()->user()?->hasPermission('finance.manage'), 403);
        $data = $this->invoiceData($request);
        $invoice = DB::transaction(fn () => $this->saveInvoice($data, $request->user()->id));
        return response()->json(['data' => $invoice->loadSum('payments', 'amount')], 201);
    }

    public function updateInvoice(Request $request, FinanceInvoice $invoice)
    {
        abort_unless(request()->user()?->hasPermission('finance.manage'), 403);
        $data = $this->invoiceData($request, $invoice);
        if ($invoice->status === 'void' && $data['status'] !== 'void') throw ValidationException::withMessages(['status' => 'Voided invoices cannot be reopened.']);
        $paid = (float) $invoice->payments()->sum('amount');
        if ($paid > (float) $data['total']) throw ValidationException::withMessages(['total' => 'Invoice total cannot be lower than payments already recorded.']);
        DB::transaction(function () use ($invoice, $data): void {
            $invoice->update($data);
            $invoice->incomeEntry?->update(['description' => $data['description'], 'amount' => $data['subtotal'], 'currency' => $data['currency'], 'entry_date' => $data['issue_date'], 'customer_id' => $data['customer_id'], 'daily_booking_id' => $data['daily_booking_id'], 'monthly_contract_id' => $data['monthly_contract_id'], 'status' => $data['status'] === 'sent' ? 'posted' : 'draft']);
        });
        return response()->json(['data' => $invoice->fresh()->loadSum('payments', 'amount')]);
    }

    public function destroyInvoice(FinanceInvoice $invoice)
    {
        abort_unless(request()->user()?->hasPermission('finance.manage'), 403);
        if ($invoice->payments()->exists()) throw ValidationException::withMessages(['invoice' => 'Invoices with recorded payments cannot be deleted. Void the invoice to keep its history.']);
        DB::transaction(function () use ($invoice): void { $entry = $invoice->incomeEntry; $invoice->delete(); $entry?->delete(); });
        return response()->noContent();
    }

    private function invoiceData(Request $request, ?FinanceInvoice $invoice = null): array
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'], 'daily_booking_id' => ['nullable', 'integer', 'exists:bookings,id'], 'monthly_contract_id' => ['nullable', 'integer', 'exists:monthly_contracts,id'],
            'description' => ['required', 'string', 'max:180'], 'issue_date' => ['required', 'date_format:Y-m-d'], 'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:issue_date'],
            'subtotal' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'], 'tax_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'total' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'], 'currency' => ['required', 'string', 'size:3', Rule::in(['MMK', 'USD'])],
            'status' => ['required', Rule::in(['draft', 'sent', 'void'])], 'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        if (abs((float) $validated['total'] - (float) $validated['subtotal'] - (float) $validated['tax_amount']) > 0.01) {
            throw ValidationException::withMessages(['total' => 'Total must equal subtotal plus tax.']);
        }
        return array_merge(['customer_id' => null, 'daily_booking_id' => null, 'monthly_contract_id' => null, 'notes' => null], $validated);
    }

    private function saveInvoice(array $data, int $userId): FinanceInvoice
    {
        $entry = FinanceEntry::create(['type' => 'income', 'category' => 'Invoice', 'description' => $data['description'], 'amount' => $data['subtotal'], 'currency' => $data['currency'], 'entry_date' => $data['issue_date'], 'status' => $data['status'] === 'sent' ? 'posted' : 'draft', 'customer_id' => $data['customer_id'], 'daily_booking_id' => $data['daily_booking_id'], 'monthly_contract_id' => $data['monthly_contract_id'], 'created_by' => $userId]);
        return FinanceInvoice::create([...$data, 'invoice_number' => 'INV-'.now()->format('ym').'-'.Str::upper(Str::random(6)), 'income_entry_id' => $entry->id, 'created_by' => $userId]);
    }

    public function payments(FinanceInvoice $invoice)
    {
        return response()->json(['data' => $invoice->payments()->orderByDesc('payment_date')->orderByDesc('id')->get()]);
    }

    public function storePayment(Request $request, FinanceInvoice $invoice)
    {
        abort_unless(request()->user()?->hasPermission('finance.manage'), 403);
        $data = $this->paymentData($request, $invoice);
        return response()->json(['data' => FinancePayment::create([...$data, 'invoice_id' => $invoice->id, 'currency' => $invoice->currency, 'created_by' => $request->user()->id])], 201);
    }

    public function updatePayment(Request $request, FinanceInvoice $invoice, FinancePayment $payment)
    {
        abort_unless(request()->user()?->hasPermission('finance.manage'), 403);
        abort_unless((int) $payment->invoice_id === (int) $invoice->id, 404);
        $data = $this->paymentData($request, $invoice, $payment);
        $payment->update($data);
        return response()->json(['data' => $payment->fresh()]);
    }

    public function destroyPayment(FinanceInvoice $invoice, FinancePayment $payment)
    {
        abort_unless(request()->user()?->hasPermission('finance.manage'), 403);
        abort_unless((int) $payment->invoice_id === (int) $invoice->id, 404);
        $payment->delete();
        return response()->noContent();
    }

    private function paymentData(Request $request, FinanceInvoice $invoice, ?FinancePayment $payment = null): array
    {
        $data = $request->validate(['payment_date' => ['required', 'date_format:Y-m-d'], 'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'], 'payment_method' => ['required', 'string', 'max:40'], 'reference' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:5000']]);
        $paid = (float) $invoice->payments()->when($payment, fn ($q) => $q->whereKeyNot($payment->id))->sum('amount');
        if ($paid + (float) $data['amount'] > (float) $invoice->total + 0.001) throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the invoice balance.']);
        if ($invoice->status === 'void' || $invoice->status === 'draft') throw ValidationException::withMessages(['invoice_id' => 'Payments can only be recorded on sent invoices.']);
        return $data;
    }

    public function report(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->entriesQuery($filters)->where('status', 'posted');
        $byCategory = (clone $query)->reorder()->selectRaw('type, category, currency, SUM(amount) AS total, COUNT(*) AS count')->groupBy('type', 'category', 'currency')->orderBy('type')->orderBy('category')->get();
        $monthExpression = DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', entry_date)" : "DATE_FORMAT(entry_date, '%Y-%m')";
        $byMonth = (clone $query)->reorder()->selectRaw("{$monthExpression} AS month, type, currency, SUM(amount) AS total")->groupBy('month', 'type', 'currency')->orderBy('month')->get();
        $daily = (clone $query)->where('vehicle_fleet', 'daily')->selectRaw('vehicle_id, currency, SUM(CASE WHEN type = \'income\' THEN amount ELSE 0 END) AS income, SUM(CASE WHEN type = \'expense\' THEN amount ELSE 0 END) AS expenses')->groupBy('vehicle_id', 'currency')->get()->map(fn ($r) => [...$r->toArray(), 'fleet' => 'daily', 'vehicle' => Car::find($r->vehicle_id)?->plate_no]);
        $monthly = (clone $query)->where('vehicle_fleet', 'monthly')->selectRaw('vehicle_id, currency, SUM(CASE WHEN type = \'income\' THEN amount ELSE 0 END) AS income, SUM(CASE WHEN type = \'expense\' THEN amount ELSE 0 END) AS expenses')->groupBy('vehicle_id', 'currency')->get()->map(fn ($r) => [...$r->toArray(), 'fleet' => 'monthly', 'vehicle' => MonthlyCar::find($r->vehicle_id)?->plate_no]);
        return response()->json(['data' => ['by_category' => $byCategory, 'by_month' => $byMonth, 'by_vehicle' => $daily->concat($monthly)->values()]]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->entriesQuery($filters)->orderBy('entry_date')->get();
        $callback = static function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Category', 'Description', 'Amount', 'Currency', 'Status', 'Reference']);
            foreach ($rows as $row) fputcsv($out, [$row->entry_date->format('Y-m-d'), $row->type, $row->category, $row->description, $row->amount, $row->currency, $row->status, $row->reference]);
            fclose($out);
        };
        return response()->streamDownload($callback, 'finance-report-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
