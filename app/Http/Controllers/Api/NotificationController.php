<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $nonLues = Notification::where('user_id', $request->user()->id)->where('est_lue', false)->count();

        return response()->json([
            'notifications' => $notifications,
            'non_lues' => $nonLues,
        ]);
    }

    public function show(string $id)
    {
        $notification = Notification::findOrFail($id);
        return response()->json($notification);
    }

    public function marquerLue(string $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['est_lue' => true]);
        return response()->json(['message' => 'Notification marquée comme lue']);
    }

    public function update(Request $request, string $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['est_lue' => true]);
        return response()->json(['message' => 'Notification mise à jour']);
    }
}