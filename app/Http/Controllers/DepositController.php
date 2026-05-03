<?php

namespace App\Http\Controllers;

use App\Models\DepositOrder;
use App\Models\User;
use App\Services\PayosService;
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

    public function store(Request $request, PayosService $payos)
    {
        $request->merge([
            'amount' => (int) preg_replace('/\D/', '', (string) $request->input('amount')),
        ]);

        $request->validate([
            'amount' => 'required|integer|min:10000|max:50000000',
        ]);

        $order = DepositOrder::create([
            'user_id' => Auth::id(),
            'code' => $this->makeCode(),
            'amount' => (int) $request->amount,
            'status' => 'pending',
            'provider' => config('deposit.provider', 'payos'),
        ]);

        if (config('deposit.provider') === 'payos' && $payos->isConfigured()) {
            try {
                $payosOrderCode = $this->makePayosOrderCode($order);
                $description = 'CVPS' . $order->id;
                $order->update([
                    'code' => $description,
                    'provider_order_code' => $payosOrderCode,
                ]);

                $payment = $payos->createPaymentLink([
                    'orderCode' => $payosOrderCode,
                    'amount' => $order->amount,
                    'description' => $description,
                    'cancelUrl' => route('deposits.show', $order->id),
                    'returnUrl' => route('deposits.show', $order->id),
                    'buyerName' => Auth::user()->name,
                    'buyerEmail' => Auth::user()->email,
                    'expiredAt' => now()->addMinutes(30)->timestamp,
                ]);

                $order->update([
                    'transaction_ref' => (string) data_get($payment, 'data.paymentLinkId'),
                    'raw_payload' => $payment,
                ]);
            } catch (\Throwable $e) {
                Log::error('PayOS create payment failed', [
                    'order_id' => $order->id,
                    'user_id' => Auth::id(),
                    'message' => $e->getMessage(),
                ]);

                return back()
                    ->withInput()
                    ->with('error', 'Không tạo được mã thanh toán PayOS: ' . $e->getMessage());
            }
        }

        return redirect()->route('deposits.show', $order->id);
    }

    public function show($id, PayosService $payos)
    {
        $order = DepositOrder::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($order->status === 'pending' && $order->provider === 'payos' && $payos->isConfigured()) {
            $this->syncPayosOrder($order, $payos);
            $order->refresh();
        }

        return view('deposits.show', compact('order'));
    }

    public function status($id, PayosService $payos)
    {
        $order = DepositOrder::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($order->status === 'pending' && $order->provider === 'payos' && $payos->isConfigured()) {
            $this->syncPayosOrder($order, $payos);
            $order->refresh();
        }

        $freshUser = User::query()->find(Auth::id());
        $balance = (int) ($freshUser->balance ?? 0);

        return response()->json([
            'status' => $order->status,
            'paid' => $order->status === 'paid',
            'paid_at' => $order->paid_at ? $order->paid_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') : null,
            'balance' => number_format($balance, 0, ',', '.') . ' VND',
        ]);
    }

    public function webhook(Request $request, PayosService $payos)
    {
        $payload = $request->all();

        Log::info('Deposit webhook received', [
            'provider' => ($payload['signature'] ?? null) ? 'payos' : request()->input('provider', 'unknown'),
            'order_code' => data_get($payload, 'data.orderCode'),
            'amount' => data_get($payload, 'data.amount') ?? $request->input('amount'),
        ]);

        if (($payload['signature'] ?? null) && isset($payload['data'])) {
            if (!$payos->verifyWebhook((array) $payload['data'], $payload['signature'])) {
                Log::warning('PayOS webhook invalid signature', ['payload' => $payload]);

                return response()->json(['message' => 'Invalid signature'], 401);
            }
        } elseif (config('deposit.webhook_secret')) {
            $secret = $request->header('X-Webhook-Secret') ?? $request->input('secret');
            if (!hash_equals(config('deposit.webhook_secret'), (string) $secret)) {
                return response()->json(['message' => 'Invalid secret'], 401);
            }
        }

        $content = $this->extractContent($payload);
        $amount = $this->extractAmount($payload);
        $reference = $this->extractReference($payload);
        $orderCode = data_get($payload, 'data.orderCode');

        if ((!$content && !$orderCode) || !$amount) {
            Log::warning('Deposit webhook missing content or amount', ['payload' => $payload]);
            return response()->json(['message' => 'Ignored'], 202);
        }

        $order = DepositOrder::where('status', 'pending')
            ->where('amount', (int) $amount)
            ->where(function ($query) use ($content, $orderCode) {
                if ($orderCode) {
                    $query->orWhere('provider_order_code', (int) $orderCode)
                        ->orWhere('id', (int) $orderCode);
                }

                if ($content) {
                    $query->orWhere('code', trim($content))
                        ->orWhereRaw('? LIKE CONCAT("%", code, "%")', [$content]);
                }
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

        $this->markOrderPaid(
            $order,
            data_get($payload, 'data.paymentLinkId') ? 'payos' : request()->input('provider', 'webhook'),
            $reference,
            $payload
        );

        return response()->json([
            'code' => '00',
            'desc' => 'success',
            'success' => true,
            'message' => 'OK',
        ]);
    }

    private function syncPayosOrder(DepositOrder $order, PayosService $payos): void
    {
        try {
            $payment = $payos->getPaymentLinkInformation($order->provider_order_code ?: $order->id);
            $status = strtoupper((string) data_get($payment, 'data.status'));

            if ($status !== 'PAID') {
                return;
            }

            $amount = (int) data_get($payment, 'data.amount');
            if ($amount !== (int) $order->amount) {
                Log::warning('PayOS sync amount mismatch', [
                    'order_id' => $order->id,
                    'order_amount' => $order->amount,
                    'payos_amount' => $amount,
                    'payload' => $payment,
                ]);

                return;
            }

            $this->markOrderPaid(
                $order,
                'payos',
                data_get($payment, 'data.paymentLinkId'),
                $payment
            );
        } catch (\Throwable $e) {
            Log::warning('PayOS sync failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function markOrderPaid(DepositOrder $order, string $provider, ?string $reference, array $payload): void
    {
        DB::transaction(function () use ($order, $provider, $reference, $payload) {
            $order = DepositOrder::whereKey($order->id)->lockForUpdate()->first();
            if (!$order || $order->status !== 'pending') {
                return;
            }

            $order->user()->increment('balance', $order->amount);
            $order->update([
                'status' => 'paid',
                'provider' => $provider,
                'transaction_ref' => $reference,
                'raw_payload' => $payload,
                'paid_at' => now(),
            ]);
        });
    }

    private function makeCode(): string
    {
        do {
            $code = 'NAP' . now()->format('ymd') . strtoupper(Str::random(6));
        } while (DepositOrder::where('code', $code)->exists());

        return $code;
    }

    private function makePayosOrderCode(DepositOrder $order): int
    {
        return ((int) now()->timestamp * 1000) + ((int) $order->id % 1000);
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
            ?? data_get($payload, 'data.transferAmount')
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
            ?? data_get($payload, 'data.transactionId')
            ?? data_get($payload, 'data.paymentLinkId');
    }
}
