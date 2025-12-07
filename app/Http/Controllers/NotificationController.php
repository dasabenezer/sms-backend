<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $userId = $request->user()->id;

        $query = Notification::where('tenant_id', $tenantId);

        // Filter by user (admin can see all, others see their own)
        if ($request->user()->role !== 'admin') {
            $query->where(function($q) use ($userId) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $userId);
            });
        } else {
            // Admin can filter by user
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
        }

        // Filter by read status
        if ($request->has('is_read')) {
            $isRead = filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_read', $isRead);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }

        $notifications = $query->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'in:info,success,warning,error',
            'user_id' => 'nullable|exists:users,id',
            'data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $notification = Notification::create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user_id,
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type ?? 'info',
            'data' => $request->data
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully',
            'data' => $notification
        ], 201);
    }

    public function markAsRead(Request $request, $id)
    {
        $tenantId = $request->user()->tenant_id;
        $userId = $request->user()->id;

        $notification = Notification::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        // Check if user has permission to mark this notification as read
        if ($request->user()->role !== 'admin' && 
            $notification->user_id !== null && 
            $notification->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $userId = $request->user()->id;

        $query = Notification::where('tenant_id', $tenantId)
            ->where('is_read', false);

        // Non-admin users can only mark their own notifications as read
        if ($request->user()->role !== 'admin') {
            $query->where(function($q) use ($userId) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $userId);
            });
        }

        $count = $query->update([
            'is_read' => true,
            'read_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read",
            'count' => $count
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $tenantId = $request->user()->tenant_id;
        $userId = $request->user()->id;

        $notification = Notification::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        // Check if user has permission to delete this notification
        if ($request->user()->role !== 'admin' && 
            $notification->user_id !== null && 
            $notification->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    public function getUnreadCount(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $userId = $request->user()->id;

        $query = Notification::where('tenant_id', $tenantId)
            ->where('is_read', false);

        // Filter by user
        if ($request->user()->role !== 'admin') {
            $query->where(function($q) use ($userId) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $userId);
            });
        }

        $count = $query->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }
}
