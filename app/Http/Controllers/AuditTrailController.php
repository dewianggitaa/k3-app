<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use Inertia\Inertia;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()->can('manage-roles'), 403, 'Anda tidak memiliki izin.');

        $query = Activity::with('causer')
            ->latest();

        // Filter by log_name (category)
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('log_name', $request->category);
        }

        // Filter by event
        if ($request->filled('event') && $request->event !== 'all') {
            $query->where('event', $request->event);
        }

        // Filter by date range
        if ($request->filled('range')) {
            $days = match($request->range) {
                'today' => 1,
                '7days' => 7,
                '30days' => 30,
                default => null,
            };
            if ($days) {
                $query->where('created_at', '>=', now()->subDays($days)->startOfDay());
            }
        }

        // Search by description or causer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHasMorph('causer', '*', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // Get distinct log_names for category filter pills
        $categories = Activity::distinct()->pluck('log_name')->filter()->values();

        return Inertia::render('AuditTrail/Index', [
            'logs'       => $logs,
            'categories' => $categories,
            'filters'    => $request->only(['category', 'event', 'range', 'search']),
        ]);
    }
}
