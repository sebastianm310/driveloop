<?php

namespace App\Modules\GestionUsuario\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MER\User;
use App\Models\MER\Vehiculo;
use App\Models\MER\Marca;
use App\Models\MER\Clase;
use Illuminate\Http\Request;

class AdminPanelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        //Administra para enviar a la vista correspondiente segun el rol del usuario
        if ($user->hasRole('Administrador') || $user->hasRole('Soporte')) {
            
            $query = Vehiculo::with(['marca', 'linea', 'clase', 'user'])
                ->orderBy('cod', 'desc');

            // filtro de vehiculos
            if ($request->filled('marca')) {
                $query->where('codmar', $request->marca);
            }

            if ($request->filled('clase')) {
                $query->where('codcla', $request->clase);
            }

            if ($request->filled('color')) {
                $query->where('col', 'like', '%' . $request->color . '%');
            }

            $vehiculos = $query->get();
            $marcas = Marca::orderBy('des')->get();
            $clases = Clase::orderBy('des')->get();

            return view('modules.GestionUsuario.admin.index', compact('vehiculos', 'marcas', 'clases'));
        } else {
            return view('modules.GestionUsuario.breeze.dashboard');
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }
    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
