<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campagne;
use App\Models\Administrateur;
use Illuminate\Http\Request;

class CampagneController extends Controller
{
    public function index()
    {
        $campagnes = Campagne::with('administrateur.user')->orderBy('date_debut', 'desc')->paginate(20);
        return response()->json($campagnes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'cible' => 'nullable|string',
            'region' => 'nullable|string',
            'type' => 'required|in:prevention,vaccination,sensibilisation',
        ]);

        $administrateur = Administrateur::firstOrCreate(['user_id' => $request->user()->id]);

        $campagne = Campagne::create([
            'administrateur_id' => $administrateur->id,
            'titre' => $request->titre,
            'description' => $request->description,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'cible' => $request->cible,
            'region' => $request->region,
            'type' => $request->type,
        ]);

        return response()->json(['message' => 'Campagne créée', 'campagne' => $campagne], 201);
    }

    public function show(string $id)
    {
        $campagne = Campagne::with('administrateur.user')->findOrFail($id);
        return response()->json($campagne);
    }

    public function update(Request $request, string $id)
    {
        $campagne = Campagne::findOrFail($id);
        $campagne->update($request->all());
        return response()->json(['message' => 'Campagne modifiée', 'campagne' => $campagne]);
    }

    public function destroy(string $id)
    {
        Campagne::findOrFail($id)->delete();
        return response()->json(['message' => 'Campagne supprimée']);
    }
}