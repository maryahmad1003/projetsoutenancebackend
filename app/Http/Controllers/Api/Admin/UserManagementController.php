<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->with(['medecin', 'patient', 'administrateur', 'pharmacien', 'laborantin'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:medecin,patient,administrateur,pharmacien,laborantin',
        ]);

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telephone' => $request->telephone,
            'role' => $request->role,
            'langue' => $request->langue ?? 'fr',
            'est_actif' => true,
        ]);

        return response()->json(['message' => 'Utilisateur créé', 'user' => $user], 201);
    }

    public function show(string $id)
    {
        $user = User::with(['medecin.centreSante', 'patient.dossierMedical', 'administrateur', 'pharmacien.pharmacie', 'laborantin.laboratoire'])->findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->only(['nom', 'prenom', 'email', 'telephone', 'role', 'langue', 'est_actif']));

        return response()->json(['message' => 'Utilisateur modifié', 'user' => $user]);
    }

    public function destroy(string $id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }
}