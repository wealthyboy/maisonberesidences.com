<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MinimumStayPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MinimumStayPeriodController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->save($request, new MinimumStayPeriod(['created_by' => $request->user()?->id]));

        return redirect()->route('admin.date-blocks.index')->with('status', 'The minimum-stay rule was added.');
    }

    public function update(Request $request, MinimumStayPeriod $minimumStayPeriod): RedirectResponse
    {
        $this->save($request, $minimumStayPeriod);

        return redirect()->route('admin.date-blocks.index')->with('status', 'The minimum-stay rule was updated.');
    }

    public function destroy(MinimumStayPeriod $minimumStayPeriod): RedirectResponse
    {
        $minimumStayPeriod->delete();

        return redirect()->route('admin.date-blocks.index')->with('status', 'The minimum-stay rule was removed. Date blocks were not changed.');
    }

    private function save(Request $request, MinimumStayPeriod $period): void
    {
        $data = $request->validate([
            'minimum_title' => ['required', 'string', 'max:120'],
            'minimum_starts_on' => ['required', 'date'],
            'minimum_ends_on' => ['required', 'date', 'after_or_equal:minimum_starts_on'],
            'minimum_nights' => ['required', 'integer', 'min:1', 'max:65535'],
            'minimum_scope' => ['required', 'in:all,selected'],
            'minimum_apartment_ids' => ['nullable', 'required_if:minimum_scope,selected', 'array', 'min:1'],
            'minimum_apartment_ids.*' => ['integer', 'distinct', 'exists:apartments,id'],
            'minimum_is_active' => ['required', 'boolean'],
        ], [
            'minimum_apartment_ids.required_if' => 'Select at least one apartment for this minimum-stay rule.',
        ]);

        DB::transaction(function () use ($period, $data): void {
            $period->fill([
                'title' => trim($data['minimum_title']),
                'starts_on' => $data['minimum_starts_on'], 'ends_on' => $data['minimum_ends_on'],
                'minimum_nights' => $data['minimum_nights'], 'applies_to_all' => $data['minimum_scope'] === 'all',
                'is_active' => $data['minimum_is_active'],
            ])->save();
            $period->apartments()->sync($period->applies_to_all ? [] : $data['minimum_apartment_ids']);
        });
    }
}
