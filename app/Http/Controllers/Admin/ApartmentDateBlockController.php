<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ApartmentDateBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApartmentDateBlockController extends Controller
{
    public function index(): View
    {
        return view('admin.date-blocks.index', [
            'apartments' => Apartment::query()->orderBy('sort_order')->orderBy('name')->get(),
            'dateBlocks' => ApartmentDateBlock::query()
                ->with(['apartments:id,name', 'creator:id,name'])
                ->orderByDesc('ends_on')
                ->orderByDesc('starts_on')
                ->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'apartment_ids' => ['required', 'array', 'min:1'],
            'apartment_ids.*' => ['required', 'integer', 'distinct', 'exists:apartments,id'],
        ], [
            'apartment_ids.required' => 'Select at least one apartment to block.',
            'ends_on.after' => 'The available-again date must be after the block start date.',
        ]);

        DB::transaction(function () use ($data, $request): void {
            $block = ApartmentDateBlock::create([
                'title' => filled($data['title'] ?? null) ? trim($data['title']) : 'Manual block',
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null,
                'created_by' => $request->user()?->id,
            ]);

            $block->apartments()->sync($data['apartment_ids']);
        });

        return redirect()
            ->route('admin.date-blocks.index')
            ->with('status', 'The selected apartments have been blocked for those dates.');
    }

    public function destroy(ApartmentDateBlock $dateBlock): RedirectResponse
    {
        $dateBlock->delete();

        return redirect()
            ->route('admin.date-blocks.index')
            ->with('status', 'The date block was removed. Those dates can now be searched again.');
    }
}
