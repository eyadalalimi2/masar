<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLocationController extends Controller
{
    public function index(): View
    {
        $locations = Location::query()->orderBy('city')->orderBy('zone')->paginate(20);

        return view('admin.locations.index', compact('locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'city' => ['required', 'string', 'max:255'],
            'zone' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Location::query()->create([
            'city' => $data['city'],
            'zone' => $data['zone'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'تمت إضافة الموقع بنجاح.');
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $data = $request->validate([
            'city' => ['required', 'string', 'max:255'],
            'zone' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $location->update([
            'city' => $data['city'],
            'zone' => $data['zone'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'تم تحديث الموقع بنجاح.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return back()->with('success', 'تم حذف الموقع بنجاح.');
    }
}
