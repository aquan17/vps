<?php

namespace App\Http\Controllers;

use App\Models\DepositOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DepositController extends Controller
{
    public function index()
    {
        $orders = DepositOrder::where('user_id', Auth::id())
            ->latest()
            ->limit(20)
            ->get();

        return view('deposits.index', compact('orders'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'amount' => (int) preg_replace('/\D/', '', (string) $request->input('amount')),
        ]);

        $request->validate([
            'amount' => 'required|integer|min:1000|max:50000000',
        ]);

        $order = DepositOrder::create([
            'user_id' => Auth::id(),
            'code' => $this->makeCode(),
            'amount' => (int) $request->amount,
            'status' => 'pending',
            'provider' => 'vietqr',
        ]);

        return redirect()->route('deposits.show', $order->id);
    }

    public function show($id)
    {
        $order = DepositOrder::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('deposits.show', compact('order'));
    }

    public function webhook(Request $request)
    {
        if (config('deposit.webhook_secret')) {
            $secret = $request->header('X-Webhook-Secret') ?? $request->input('secret');
            if (!hash_equals(config('deposit.webhook_secret'), (string) $secret)) {
                return response()->json(['message' => 'Invalid secret'], 401);
            }
        }

        $payload = $request->all();
        $content = $this->extractContent($payload);
        $amount = $this->extractAmount($payload);
        $reference = $this->extractReference($payload);

        if (!$content || !$amount) {
            Log::warning('Deposit webhook missing content or amount', ['payload' => $payload]);
            return response()->json(['message' => 'Ignored'], 202);
        }

        $order = DepositOrder::where('status', 'pending')
            ->where('amount', (int) $amount)
            ->where(function ($query) use ($content) {
                $query->where('code', trim($content))
                    ->orWhereRaw('? LIKE CONCAT("%", code, "%")', [$content]);
            })
            ->first();

        if (!$order) {
            Log::warning('Deposit webhook unmatched transaction', [
                'amount' => $amount,
                'content' => $content,
                'reference' => $reference,
                'payload' => $payload,
            ]);

            return response()->json(['message' => 'No matching order'], 202);
        }

        DB::transaction(function () use ($order, $payload, $reference) {
            $order = DepositOrder::whereKey($order->id)->lockForUpdate()->first();
            if ($order->status !== 'pending') {
                return;
            }

            $order->user()->increment('balance', $order->amount);
            $order->update([
                'status' => 'paid',
                'provider' => request()->input('provider', 'webhook'),
                'transaction_ref' => $reference,
                'raw_payload' => $payload,
                'paid_at' => now(),
            ]);
        });

        return response()->json(['message' => 'OK']);
    }

    private function makeCode(): string
    {
        do {
            $code = 'NAP' . now()->format('ymd') . strtoupper(Str::random(6));
        } while (DepositOrder::where('code', $code)->exists());

        return $code;
    }

    private function extractContent(array $payload): ?string
    {
        return $payload['content']
            ?? $payload['description']
            ?? $payload['addInfo']
            ?? data_get($payload, 'data.description')
            ?? data_get($payload, 'data.content')
            ?? data_get($payload, 'transaction.content');
    }

    private function extractAmount(array $payload): ?int
    {
        $amount = $payload['amount']
            ?? data_get($payload, 'data.amount')
            ?? data_get($payload, 'transaction.amount')
            ?? data_get($payload, 'transferAmount');

        return $amount !== null ? (int) $amount : null;
    }

    private function extractReference(array $payload): ?string
    {
        return $payload['reference']
            ?? $payload['transaction_ref']
            ?? data_get($payload, 'data.reference')
            ?? data_get($payload, 'transaction.reference')
            ?? data_get($payload, 'data.transactionId');
    }
}
