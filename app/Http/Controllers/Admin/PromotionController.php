<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(): View
    {
        return view('admin.promotions.index', [
            'promotions' => Promotion::query()->with('apartment')->latest('updated_at')->latest('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.promotions.form', [
            'promotion' => new Promotion(['scope' => 'global', 'discount_type' => 'percent', 'is_active' => true]),
            'apartments' => Apartment::query()->orderBy('sort_order')->orderBy('name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $promotion = DB::transaction(function () use ($data): Promotion {
            $promotion = Promotion::create($data);
            $this->deactivateCompetingPromotions($promotion);
            return $promotion;
        });

        return redirect()->route('admin.promotions.index')
            ->with('status', 'Discount "'.$promotion->name.'" created.');
    }

    public function edit(Promotion $promotion): View
    {
        return view('admin.promotions.form', [
            'promotion' => $promotion,
            'apartments' => Apartment::query()->orderBy('sort_order')->orderBy('name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($promotion, $data): void {
            $promotion->update($data);
            $this->deactivateCompetingPromotions($promotion->fresh());
        });

        return redirect()->route('admin.promotions.index')
            ->with('status', 'Discount updated.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return redirect()->route('admin.promotions.index')
            ->with('status', 'Discount deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'scope' => ['required', 'in:global,apartment'],
            'apartment_id' => ['nullable', 'required_if:scope,apartment', 'integer', 'exists:apartments,id'],
            'discount_type' => ['required', 'in:percent,fixed_price'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'promo_text' => ['nullable', 'string', 'max:160'],
            'ends_on' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($data['discount_type'] === 'percent' && (float) $data['discount_value'] >= 100) {
            throw ValidationException::withMessages([
                'discount_value' => 'Percentage discounts must be less than 100%.',
            ]);
        }

        return [
            'name' => trim($data['name']),
            'scope' => $data['scope'],
            'apartment_id' => $data['scope'] === 'apartment' ? (int) $data['apartment_id'] : null,
            'discount_type' => $data['discount_type'],
            'discount_value' => (float) $data['discount_value'],
            'promo_text' => filled($data['promo_text'] ?? null) ? trim($data['promo_text']) : null,
            'ends_on' => filled($data['ends_on'] ?? null) ? $data['ends_on'] : null,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function deactivateCompetingPromotions(Promotion $promotion): void
    {
        if (! $promotion->is_active) {
            return;
        }

        Promotion::query()
            ->whereKeyNot($promotion->id)
            ->where('is_active', true)
            ->where('scope', $promotion->scope)
            ->when(
                $promotion->scope === 'apartment',
                fn ($query) => $query->where('apartment_id', $promotion->apartment_id),
                fn ($query) => $query->whereNull('apartment_id'),
            )
            ->update(['is_active' => false]);
    }
}
