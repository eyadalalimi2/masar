<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Http\Controllers\Controller;
use App\Support\OptionLists;
use App\Models\Admin\Banner;
use App\Models\Admin\Broadcast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminContentController extends Controller
{
    public function index(): View
    {
        $banners = Banner::query()->latest()->paginate(10, ['*'], 'banners_page');
        $broadcasts = Broadcast::query()->latest()->paginate(10, ['*'], 'broadcasts_page');

        return view('admin.content.index', compact('banners', 'broadcasts'));
    }

    public function storeBanner(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image_url' => ['required', 'url', 'max:2000'],
            'link_url' => ['nullable', 'url', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Banner::query()->create([
            'title' => $data['title'],
            'image_url' => $data['image_url'],
            'link_url' => $data['link_url'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'تمت إضافة البنر بنجاح.');
    }

    public function updateBanner(Request $request, Banner $banner): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image_url' => ['required', 'url', 'max:2000'],
            'link_url' => ['nullable', 'url', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $banner->update([
            'title' => $data['title'],
            'image_url' => $data['image_url'],
            'link_url' => $data['link_url'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'تم تحديث البنر بنجاح.');
    }

    public function destroyBanner(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return back()->with('success', 'تم حذف البنر بنجاح.');
    }

    public function storeBroadcast(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'target_type' => ['required', 'in:' . implode(',', OptionLists::BROADCAST_TARGET_TYPES)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Broadcast::query()->create([
            'title' => $data['title'],
            'message' => $data['message'],
            'target_type' => $data['target_type'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'تمت إضافة الرسالة العامة بنجاح.');
    }

    public function updateBroadcast(Request $request, Broadcast $broadcast): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'target_type' => ['required', 'in:' . implode(',', OptionLists::BROADCAST_TARGET_TYPES)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $broadcast->update([
            'title' => $data['title'],
            'message' => $data['message'],
            'target_type' => $data['target_type'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'تم تحديث الرسالة العامة بنجاح.');
    }

    public function destroyBroadcast(Broadcast $broadcast): RedirectResponse
    {
        $broadcast->delete();

        return back()->with('success', 'تم حذف الرسالة العامة بنجاح.');
    }
}
