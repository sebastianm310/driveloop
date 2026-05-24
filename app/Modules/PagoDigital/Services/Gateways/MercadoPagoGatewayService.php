<?php

namespace App\Modules\PagoDigital\Services\Gateways;

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MercadoPagoGatewayService implements PaymentGatewayInterface
{
    public function createPayment(array $data): array
    {
        try {
            // Inicializar el SDK con las credenciales de Sandbox
            $accessToken = config('payments.mercadopago.access_token');
            if (empty($accessToken)) {
                throw new \Exception("Mercado Pago Access Token no está configurado.");
            }

            if (app()->environment('local')) {
                MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
            }
            MercadoPagoConfig::setAccessToken($accessToken);

            $client = new PreferenceClient();

            // Construir la preferencia
            $preferenceData = [
                "items" => [
                    [
                        "title" => "Alquiler de Vehículo #" . $data['codveh'],
                        "quantity" => 1,
                        "unit_price" => (float) $data['monto'],
                        "currency_id" => "COP"
                    ]
                ],
                "back_urls" => [
                    "success" => route('checkout.exito', ['id' => $data['reserva_id']]),
                    "failure" => route('checkout.error'),
                    "pending" => route('checkout.pending'),
                ],
                "auto_return" => "approved",
                "external_reference" => $data['reserva_id'],
                // NOTA: Para recibir webhooks en local, debes usar una URL expuesta públicamente (ej. ngrok)
                "notification_url" => route('pagos.webhook', ['provider' => 'mercadopago']),
            ];

            $preference = $client->create($preferenceData);

            return [
                'status' => 'redirect',
                'url' => $preference->sandbox_init_point ?? $preference->init_point,
                'reference' => 'MP-' . strtoupper(Str::random(10)),
                'external_payment_id' => $preference->id, // ID de la preferencia
                'external_reference' => $data['reserva_id'],
                'status_detail' => 'awaiting_preference_checkout',
                'message' => 'Preferencia de Mercado Pago creada con éxito.',
            ];

        } catch (\Throwable $e) {
            Log::error('Error creando preferencia en Mercado Pago: ' . $e->getMessage());
            return [
                'status' => 'rechazado',
                'message' => 'Error al conectar con la pasarela de pagos: ' . $e->getMessage(),
            ];
        }
    }
}