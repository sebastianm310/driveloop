<?php

namespace App\Modules\PagoDigital\Services;

use App\Models\MER\Pago;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use Illuminate\Support\Facades\Log;

class PaymentWebhookService
{
    public function handle(string $provider, array $payload): array
    {
        Log::info('Webhook recibido', [
            'provider' => $provider,
            'payload' => $payload,
        ]);

        $reference = null;
        $statusRecibido = 'pending';
        $statusDetail = null;
        $externalPaymentId = null;

        if ($provider === 'mercadopago') {
            // Procesamiento específico de Mercado Pago Webhook / IPN
            $paymentId = $payload['data']['id'] ?? $payload['id'] ?? null;
            $type = $payload['type'] ?? $payload['topic'] ?? null;

            // Ignorar notificaciones que no correspondan a un pago
            if (!$paymentId || ($type !== 'payment' && $type !== 'chargeback')) {
                return [
                    'ok' => true,
                    'message' => 'Notificación de Mercado Pago ignorada (tipo o ID no corresponde a un pago).',
                ];
            }

            try {
                $accessToken = config('payments.mercadopago.access_token');
                if (app()->environment('local')) {
                    MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
                }
                MercadoPagoConfig::setAccessToken($accessToken);

                $client = new PaymentClient();
                $payment = $client->get($paymentId);

                $reference = $payment->external_reference;
                $statusRecibido = $payment->status;
                $statusDetail = $payment->status_detail;
                $externalPaymentId = $payment->id;

            } catch (\Throwable $e) {
                Log::error('Error consultando el pago en Mercado Pago desde el webhook: ' . $e->getMessage());
                return [
                    'ok' => false,
                    'message' => 'Error al consultar la API de Mercado Pago.',
                ];
            }
        } else {
            // Comportamiento genérico por defecto
            $reference = $payload['reference']
                ?? $payload['data']['reference']
                ?? $payload['external_reference']
                ?? null;

            $statusRecibido = $payload['status']
                ?? $payload['data']['status']
                ?? 'pending';
        }

        if (!$reference) {
            return [
                'ok' => true,
                'message' => 'Webhook procesado sin referencia interna vinculada.',
            ];
        }

        $pago = Pago::where('referencia', $reference)
            ->orWhere('external_reference', $reference)
            ->first();

        if (!$pago) {
            return [
                'ok' => true,
                'message' => 'No se encontró pago asociado a la referencia: ' . $reference,
            ];
        }

        $estadoNormalizado = $this->normalizarEstado($statusRecibido);
        $estadoAnterior = $pago->estado;

        $pago->estado = $estadoNormalizado;
        $pago->webhook_payload = $payload;
        if ($externalPaymentId) {
            $pago->external_payment_id = $externalPaymentId;
        }
        if ($statusDetail) {
            $pago->status_detail = $statusDetail;
        }

        // Si el pago pasa de no aprobado a aprobado
        if ($estadoNormalizado === 'aprobado' && $estadoAnterior !== 'aprobado') {
            $pago->approved_at = now();
            $pago->save();

            $reserva = $pago->reserva;
            if ($reserva) {
                $reserva->codestres = 1; // 1 = Pendiente a iniciar / Activa
                $reserva->save();

                // Generar documentos (PDF de contrato, póliza, etc.)
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
                    Log::error('Error generando documentos desde webhook: ' . $e->getMessage());
                }

                // Disparar evento para enviar correos a los usuarios
                \Illuminate\Support\Facades\Event::dispatch(new \App\Modules\BusquedaReserva\Events\ReservaPagada($reserva));
            }
        } elseif ($estadoNormalizado === 'rechazado' && $estadoAnterior !== 'rechazado') {
            $pago->save();

            $reserva = $pago->reserva;
            if ($reserva) {
                $reserva->codestres = 3; // 3 = Cancelada / Finalizada
                $reserva->save();

                $vehiculo = $reserva->vehiculo;
                if ($vehiculo) {
                    $vehiculo->disp = 1; // Liberar vehículo
                    $vehiculo->save();
                }
            }
        } else {
            $pago->save();
        }

        return [
            'ok' => true,
            'message' => 'Webhook procesado correctamente. Estado anterior: ' . $estadoAnterior . ' -> Nuevo: ' . $estadoNormalizado,
        ];
    }

    private function normalizarEstado(?string $estado): string
    {
        $estado = strtolower(trim((string) $estado));

        return match ($estado) {
            'approved', 'aprobado' => 'aprobado',
            'pending', 'pendiente', 'in_process' => 'pendiente',
            'rejected', 'rechazado', 'failed', 'fallido', 'cancelled', 'refunded' => 'rechazado',
            default => 'pendiente',
        };
    }
}