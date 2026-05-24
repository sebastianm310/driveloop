<x-page>

    @php
        $dias_reserva = $reserva->fecini->diffInDays($reserva->fecfin);
        if ($dias_reserva < 1) {
            $dias_reserva = 1;
        }

        $precio_unitario = $monto / $dias_reserva;
    @endphp

    <div class="relative min-h-screen py-12 bg-white">
        <div>
            <div class="relative z-10 max-w-6xl mx-auto px-6">

                <div class="text-center mb-8">
                    <h1 class="text-4xl font-bold text-gray-900" style="font-family: 'Segoe UI', sans-serif;">
                        Checkout
                    </h1>
                    <p class="text-gray-500 mt-2 text-sm">
                        Revisa los detalles de tu reserva y procede al pago seguro.
                    </p>
                </div>

                <div class="grid lg:grid-cols-[1fr_360px] gap-8 items-start">

                    {{-- ===== IZQUIERDA: DISEÑO PREMIUM PAGO SEGURO MERCADO PAGO ===== --}}
                    <x-card class="bg-gradient-to-br from-gray-50 to-white p-6 md:p-8 space-y-6 text-left border-0">
                        
                        {{-- Cabecera de Plataforma --}}
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center flex-shrink-0 text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 leading-snug">Plataforma de Pago</h3>
                        </div>

                        <hr class="border-gray-100" />

                        {{-- Información del Proveedor --}}
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Procesado por</span>
                                <img src="{{ asset('images/logo_mercadopago.svg') }}" alt="Mercado Pago" class="h-6 w-auto object-contain" />
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed">
                                Serás redirigido de forma segura a la pasarela de <strong>Mercado Pago</strong> para completar tu transacción de manera rápida y confiable. Allí podrás elegir cómodamente tu método de pago preferido (tarjetas de crédito, PSE, Nequi, entre otros).
                            </p>
                        </div>

                        {{-- Métodos soportados pasivos --}}
                        <div class="bg-white rounded-xl border border-gray-100 p-4 space-y-3">
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider text-center md:text-left">
                                Métodos de pago aceptados en la pasarela:
                            </span>
                            <div class="grid grid-cols-4 gap-3 items-center justify-items-center">
                                {{-- Tarjetas --}}
                                <div class="flex flex-col items-center gap-1">
                                    <div class="flex items-center gap-1.5 h-10">
                                        <img src="{{ asset('images/logo_visa.svg') }}" alt="Visa" class="h-6 w-auto object-contain" />
                                        <img src="{{ asset('images/logo_mastercard.svg') }}" alt="Mastercard" class="h-6 w-auto object-contain" />
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-medium">Tarjetas</span>
                                </div>

                                {{-- PSE --}}
                                <div class="flex flex-col items-center gap-1">
                                    <div class="h-10 flex items-center justify-center overflow-visible">
                                        <img src="{{ asset('images/logo_pse.png') }}" alt="PSE" class="h-12 w-auto object-contain scale-[1.3]" />
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-medium">PSE</span>
                                </div>

                                {{-- Nequi --}}
                                <div class="flex flex-col items-center gap-1">
                                    <div class="h-10 flex items-center justify-center">
                                        <img src="{{ asset('images/nequi_logo.svg') }}" alt="Nequi" class="h-8 w-auto object-contain" />
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-medium">Nequi</span>
                                </div>

                                {{-- Efectivo --}}
                                <div class="flex flex-col items-center gap-1">
                                    <div class="h-10 flex items-center justify-center text-emerald-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-8 h-8">
                                            <rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" fill="none" />
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" fill="none" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9h.01M18 9h.01M6 15h.01M18 15h.01" />
                                        </svg>
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-medium">Efectivo</span>
                                </div>
                            </div>
                        </div>


                    </x-card>

                    {{-- ===== DERECHA: RESUMEN ===== --}}
                    <x-card class="bg-white overflow-hidden border-0">

                        <div class="px-5 pt-5 pb-4 grid grid-cols-2 gap-3 border-b border-gray-100">
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-gray-400 font-semibold">Fecha y hora de recogida</p>
                                <p class="font-semibold text-gray-800 text-sm mt-1">
                                    {{ $reserva->fecini->format('d/m/Y') }}
                                </p>
                                <p class="text-sm text-gray-400">{{ $reserva->fecini->format('g:i a') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-gray-400 font-semibold">Fecha y hora de entrega</p>
                                <p class="font-semibold text-gray-800 text-sm mt-1">
                                    {{ $reserva->fecfin->format('d/m/Y') }}
                                </p>
                                <p class="text-sm text-gray-400">{{ $reserva->fecfin->format('g:i a') }}</p>
                            </div>
                        </div>

                        <div class="px-5 py-4 border-b border-gray-100">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Días de alquiler</label>
                            <input type="text" value="{{ $dias_reserva }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm text-gray-800 bg-gray-50"
                                readonly />
                        </div>

                        <div class="px-5 pt-5">
                            @php
                                $foto = optional($reserva->vehiculo->fotos->first())->ruta;

                                if ($foto) {
                                    if (\Illuminate\Support\Str::startsWith($foto, ['http://', 'https://'])) {
                                        $rutaImagen = $foto;
                                    } else {
                                        $rutaImagen = \Illuminate\Support\Facades\Storage::disk('public')->url($foto);
                                    }
                                } else {
                                    $rutaImagen =
                                        'https://placehold.co/600x280/ef4444/ffffff?text=' .
                                        urlencode(
                                            ($reserva->vehiculo->marca->des ?? '') .
                                            ' ' .
                                            ($reserva->vehiculo->linea->des ?? ''),
                                        );
                                }
                            @endphp

                            <img src="{{ $rutaImagen }}" class="w-full rounded-xl object-cover aspect-[2.14/1]" alt="Vehículo" />
                        </div>

                        <div class="px-5 pb-4">
                            <div class="mt-2 space-y-1">
                                <p class="text-sm text-gray-700">
                                    <span class="font-semibold">Marca:</span>
                                    {{ $reserva->vehiculo->marca->des ?? 'Sin marca' }}
                                </p>

                                <p class="text-sm text-gray-700">
                                    <span class="font-semibold">Línea:</span>
                                    {{ $reserva->vehiculo->linea->des ?? 'Sin línea' }}
                                </p>

                                <p class="text-sm text-gray-700">
                                    <span class="font-semibold">Modelo:</span>
                                    {{ $reserva->vehiculo->mod ?? '' }}
                                </p>

                                <p class="text-sm text-gray-700">
                                    <span class="font-semibold">Ubicación:</span>
                                    {{ $reserva->vehiculo->ciudad->des ?? 'Sin ubicación' }}
                                </p>
                            </div>
                        </div>

                        <div class="px-5 py-3 flex items-center gap-3 text-xs text-gray-500 border-t border-gray-100">
                            <span class="flex items-center gap-1">👤 {{ $reserva->vehiculo->pas }} Personas</span>
                            <span class="text-gray-200">|</span>
                            <span class="flex items-center gap-1">⭐ 4.8 / 5 (41 reseñas)</span>
                        </div>

                        <div class="px-5 py-4 border-t border-gray-100">
                            <p class="text-3xl font-bold text-gray-900">
                                ${{ number_format($precio_unitario, 0, ',', '.') }}
                                <span class="text-base font-normal text-gray-400">Precio diario</span>
                            </p>
                        </div>

                        <div class="px-5 py-4 border-t border-gray-100">
                            <p class="text-2xl font-bold text-gray-900" id="valor-total">
                                ${{ number_format($monto, 0, ',', '.') }}
                                <span class="text-base font-normal text-gray-400">Precio total</span>
                            </p>
                        </div>

                        <div class="px-5 pb-5">
                            <form action="{{ route('checkout.pagar') }}" method="POST" id="form-pago">
                                @csrf

                                <input type="hidden" name="reserva_id" value="{{ $reserva_id }}">
                                <input type="hidden" name="codveh" value="{{ $reserva->vehiculo->cod }}">
                                <input type="hidden" name="pickup_date" value="{{ $reserva->fecini->format('Y-m-d') }}">
                                <input type="hidden" name="return_date" value="{{ $reserva->fecfin->format('Y-m-d') }}">
                                <input type="hidden" name="monto" value="{{ $monto }}">
                                <input type="hidden" name="provider" value="mercadopago">
                                <input type="hidden" name="metodo_pago" id="metodo_pago" value="transfer">

                                <x-button type="primary"
                                    class="w-full flex items-center justify-center !py-2.5 !px-4 !text-xs shadow-md !tracking-wider">
                                    Pagar ahora
                                </x-button>
                            </form>
                        </div>

                    </x-card>
                </div>
            </div>
        </div>
    </div>

</x-page>