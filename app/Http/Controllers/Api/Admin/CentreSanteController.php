<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CentreSante;
use Illuminate\Http\Request;

class CentreSanteController extends Controller
{
    public function index()
    {
        $centres = CentreSante::with(['medecins.user'])->withCount('medecins')->paginate(20);
        return response()->json($centres);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'adresse' => 'required|string',
            'type' => 'required|in:hopital,clinique,centre_sante,poste_sante',
            'region' => 'required|string',
        ]);

        $centre = CentreSante::create($request->all());
        return response()->json(['message' => 'Centre de santé créé', 'centre' => $centre], 201);
    }

    public function show(string $id)
    {
        $centre = CentreSante::with(['medecins.user'])->withCount('medecins')->findOrFail($id);
        return response()->json($centre);
    }

    public function update(Request $request, string $id)
    {
        $centre = CentreSante::findOrFail($id);
        $centre->update($request->all());
        return response()->json(['message' => 'Centre modifié', 'centre' => $centre]);
    }

    public function destroy(string $id)
    {
        CentreSante::findOrFail($id)->delete();
        return response()->json(['message' => 'Centre supprimé']);
    }
}