<?php

namespace App\Http\Controllers\Admin;

use App\Models\{ActivityLog, User};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityController
{
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', Rule::in(array_keys(ActivityLog::ACTIONS))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $logs = ActivityLog::with('user')->latest();
        if ($request->filled('user_id')) $logs->where('user_id', $request->integer('user_id'));
        if ($request->filled('action')) $logs->where('action', $request->input('action'));
        if ($request->filled('from')) $logs->whereDate('created_at', '>=', $request->input('from'));
        if ($request->filled('to')) $logs->whereDate('created_at', '<=', $request->input('to'));

        return view('admin.activities', [
            'logs' => $logs->paginate(25)->withQueryString(),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'actions' => ActivityLog::ACTIONS,
        ]);
    }
}
