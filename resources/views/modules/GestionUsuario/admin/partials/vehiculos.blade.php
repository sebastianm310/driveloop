@php
    // $vehiculos, $marcas, $clases, $colores estan en AdminPanelController
@endphp

<div class="mb-8 p-6 bg-white border border-gray-200 rounded-2xl shadow-sm">
    <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('Filtros') }}</h3>
    
    <form action="{{ route('dashboard') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <input type="hidden" name="tab" value="vehiculos">
        {{-- Marca --}}
        <div>
            <label for="marca" class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
            <select name="marca" id="marca" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                <option value="">Todas las marcas</option>
                @foreach($marcas as $marca)
                    <option value="{{ $marca->cod }}" {{ request('marca') == $marca->cod ? 'selected' : '' }}>
                        {{ $marca->des }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Clase --}}
        <div>
            <label for="clase" class="block text-sm font-medium text-gray-700 mb-2">Clase</label>
            <select name="clase" id="clase" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                <option value="">Todas las clases</option>
                @foreach($clases as $clase)
                    <option value="{{ $clase->cod }}" {{ request('clase') == $clase->cod ? 'selected' : '' }}>
                        {{ $clase->des }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Color --}}
        <div>
            <label for="color" class="block text-sm font-medium text-gray-700 mb-2">Color</label>
            <input type="text" name="color" id="color" value="{{ request('color') }}" 
                placeholder="Ej: Rojo, Azul..."
                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
        </div>

        {{-- Botones --}}
        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold py-2 px-4 rounded-xl hover:shadow-lg transition-all active:scale-95 uppercase text-sm">
                {{ __('FILTRAR') }}
            </button>
            <a href="{{ route('dashboard', ['tab' => 'vehiculos']) }}" class="py-2 px-4 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                {{ __('Limpiar') }}
            </a>
        </div>
    </form>
</div>

<x-card class="w-full p-8">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-left">{{ __('Vehículos Registrados') }}</h3>
        <span class="text-sm text-gray-500">Total: {{ $vehiculos->count() }}</span>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y text-gray-500">
            <thead class="bg-gray-200 text-xs font-medium uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-2 text-left">ID</th>
                    <th class="px-4 py-2 text-left">Marca</th>
                    <th class="px-4 py-2 text-left">Linea</th>
                    <th class="px-4 py-2 text-left">Modelo</th>
                    <th class="px-4 py-2 text-left">Clase</th>
                    <th class="px-4 py-2 text-left">Color</th>
                    <th class="px-4 py-2 text-left">Propietario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse ($vehiculos as $vehiculo)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-2 whitespace-nowrap font-medium text-gray-900">{{ $vehiculo->cod }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">{{ $vehiculo->marca->des ?? 'N/A' }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">{{ $vehiculo->linea->des ?? 'N/A' }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">{{ $vehiculo->mod }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">{{ $vehiculo->clase->des ?? 'N/A' }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $vehiculo->col }}
                            </span>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            @if($vehiculo->user)
                                {{ $vehiculo->user->nom }} {{ $vehiculo->user->ape }}
                            @else
                                <span class="text-gray-400">Sin propietario</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>No hay vehículos que coincidan con los filtros.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>