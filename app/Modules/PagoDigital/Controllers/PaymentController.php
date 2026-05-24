<?php

namespace App\Modules\PagoDigital\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MER\Vehiculo;
use App\Models\MER\Reserva;
use App\Modules\PagoDigital\Services\PaymentService;
use App\Modules\PagoDigital\Services\PaymentWebhookService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected PaymentWebhookService $paymentWebhookService
    ) {}

    public function checkoutReserva(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'codveh' => 'required|exists:vehiculos,cod',
            'pickup_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after_or_equal:pickup_date',
        ]);

        $vehiculo = Vehiculo::with(['marca', 'linea', 'ciudad', 'fotos', 'combustible'])
            ->findOrFail($request->codveh);

        if (!(bool) $vehiculo->disp) {
            return redirect()->back()->with('error', 'El vehículo ya no se encuentra disponible.');
        }

        $fecini = Carbon::parse($request->pickup_date);
        $fecfin = Carbon::parse($request->return_date);

        $reservaActivaSolapada = Reserva::where('codveh', $vehiculo->cod)
            ->where('codestres', '!=', 3)
            ->where(function ($q) use ($fecini, $fecfin) {
                $q->where('fecini', '<', $fecfin)
                    ->where('fecfin', '>', $fecini);
            })
            ->exists();

        if ($reservaActivaSolapada) {
            return redirect()->back()->with('error', 'El vehículo ya está reservado para esas fechas.');
        }

        $dias = $fecini->diffInDays($fecfin);
        if ($dias < 1) {
            $dias = 1;
        }

        $monto = (float) $vehiculo->prerent * $dias;

        $reserva = new \stdClass();
        $reserva->cod = null;
        $reserva->fecini = $fecini;
        $reserva->fecfin = $fecfin;
        $reserva->vehiculo = $vehiculo;

        $reserva_id = 'TMP-' . now()->format('YmdHis') . '-' . $vehiculo->cod;

        return view('modules.PagoDigital.checkout', [
            'reserva' => $reserva,
            'monto' => $monto,
            'reserva_id' => $reserva_id,
        ]);
    }

    public function procesarPago(Request $request)
    {
        $data = $request->validate([
            'reserva_id' => 'required|string',
            'codveh' => 'required|exists:vehiculos,cod',
            'pickup_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after_or_equal:pickup_date',
            'metodo_pago' => 'required|string|in:card,transfer,nequi',
            'monto' => 'required|numeric|min:1',
            'provider' => 'required|string|in:simulated,mercadopago,wompi',
            'nequi_telefono' => 'nullable|string|max:20',
            'card_numero' => 'nullable|string|max:25',
            'card_nombre' => 'nullable|string|max:120',
            'card_expiry' => 'nullable|string|max:10',
            'card_cvv' => 'nullable|string|max:6',
            'transfer_comprobante' => 'nullable|string|max:255',
        ]);

        $result = $this->paymentService->process($data, auth()->id());

        if ($result['status'] === 'aprobado') {
            return redirect()->route('checkout.exito', $result['reserva_id'])
                ->with('success', $result['message'] ?? 'Pago aprobado correctamente.');
        }

        if ($result['status'] === 'pendiente') {
            return redirect()->route('checkout.pending')
                ->with('success', $result['message'] ?? 'El pago quedó pendiente.');
        }

        if ($result['status'] === 'redirect') {
            return redirect()->away($result['url']);
        }

        return redirect()->route('checkout.error')
            ->with('error', $result['message'] ?? 'No fue posible procesar el pago.');
    }

    public function success(Request $request, $id)
    {
        \Illuminate\Support\Facades\Log::info('Página de éxito - params:', $request->query());

        // Resolver referencia temporal TMP- al ID real de la reserva
        if (str_starts_with($id, 'TMP-')) {
            $pago = \App\Models\MER\Pago::where('external_reference', $id)->first();
            if ($pago) {
                $id = $pago->codres;
            }
        }

        // Fallback: verificar el pago directamente con Mercado Pago
        // (en caso de que el webhook no haya llegado por el túnel local)
        $this->verificarPagoConMercadoPago($request, $id);

        return view('modules.PagoDigital.success', compact('id'));
    }

    public function failure()
    {
        return view('modules.PagoDigital.failure');
    }

    public function pending(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Página de pendiente - params:', $request->query());

        // Mercado Pago envía payment_id o collection_id
        $paymentId = $request->query('payment_id') ?? $request->query('collection_id');

        if ($paymentId) {
            try {
                $accessToken = config('payments.mercadopago.access_token');
                if (app()->environment('local')) {
                    \MercadoPago\MercadoPagoConfig::setRuntimeEnviroment(\MercadoPago\MercadoPagoConfig::LOCAL);
                }
                \MercadoPago\MercadoPagoConfig::setAccessToken($accessToken);

                $client = new \MercadoPago\Client\Payment\PaymentClient();
                $payment = $client->get($paymentId);

                \Illuminate\Support\Facades\Log::info('Verificación de pago desde pending:', [
                    'payment_id' => $paymentId,
                    'status' => $payment->status ?? 'unknown',
                    'status_detail' => $payment->status_detail ?? 'unknown',
                    'external_reference' => $payment->external_reference ?? 'none',
                ]);

                if (($payment->status === 'approved' || $payment->status === 'pending') && !empty($payment->external_reference)) {
                    $pago = \App\Models\MER\Pago::where('external_reference', $payment->external_reference)->first();

                    if ($pago) {
                        if ($pago->estado !== 'aprobado') {
                            if ($payment->status === 'approved') {
                                $this->aprobarPago($pago, $payment);
                            } elseif ($payment->status === 'pending' && app()->environment('local')) {
                                // En desarrollo local/sandbox, aprobamos el pago aunque venga como 'pending' para simular flujos (como PSE)
                                $this->aprobarPago($pago, $payment);
                            }
                        }

                        if ($pago->estado === 'aprobado') {
                            // Redirigir a la página de éxito
                            return redirect()->route('checkout.exito', $pago->codres)
                                ->with('success', '¡Pago aprobado exitosamente!');
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Error verificando pago desde pending: ' . $e->getMessage());
            }
        }

        return view('modules.PagoDigital.pending');
    }

    /**
     * Verificar el pago directamente con Mercado Pago API (fallback para cuando el webhook no llega).
     */
    private function verificarPagoConMercadoPago(Request $request, $reservaId): void
    {
        $paymentId = $request->query('payment_id') ?? $request->query('collection_id');
        if (!$paymentId) {
            return;
        }

        try {
            $pago = \App\Models\MER\Pago::where('codres', $reservaId)->first();
            if (!$pago || $pago->estado === 'aprobado') {
                return;
            }

            $accessToken = config('payments.mercadopago.access_token');
            if (app()->environment('local')) {
                \MercadoPago\MercadoPagoConfig::setRuntimeEnviroment(\MercadoPago\MercadoPagoConfig::LOCAL);
            }
            \MercadoPago\MercadoPagoConfig::setAccessToken($accessToken);

            $client = new \MercadoPago\Client\Payment\PaymentClient();
            $payment = $client->get($paymentId);

            \Illuminate\Support\Facades\Log::info('Verificación de pago desde success:', [
                'payment_id' => $paymentId,
                'status' => $payment->status ?? 'unknown',
            ]);

            if ($payment->status === 'approved') {
                $this->aprobarPago($pago, $payment);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error en fallback de verificación de pago: ' . $e->getMessage());
        }
    }

    /**
     * Aprobar un pago y disparar generación de documentos + correos.
     */
    private function aprobarPago(\App\Models\MER\Pago $pago, $mpPayment): void
    {
        $pago->estado = 'aprobado';
        $pago->approved_at = now();
        $pago->external_payment_id = $mpPayment->id;
        $pago->status_detail = $mpPayment->status_detail;
        $pago->save();

        $reserva = $pago->reserva;
        if ($reserva) {
            $reserva->codestres = 1; // Activa
            $reserva->save();

            // Generar documentos PDF
            try {
                $reserva->load([
                    'user',
                    'vehiculo.marca',
                    'vehiculo.linea',
                    'vehiculo.clase',
                    'vehiculo.combustible',
                    'vehiculo.ciudad',
                    'vehiculo.poliza_vehiculo',
                    'pago',
                ]);
                $documentoService = app(\App\Services\Reservas\ReservaDocumentoService::class);
                $documentoService->generarYEnviar($reserva);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Error generando documentos desde fallback: ' . $e->getMessage());
            }

            // Disparar evento para correos de confirmación
            \Illuminate\Support\Facades\Event::dispatch(
                new \App\Modules\BusquedaReserva\Events\ReservaPagada($reserva)
            );
        }
    }

    public function webhook(Request $request, string $provider)
    {
        $result = $this->paymentWebhookService->handle($provider, $request->all());

        return response()->json($result, 200);
    }
}