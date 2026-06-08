<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminModules;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $sections = AdminModules::sections();
        $modules = AdminModules::all();

        $priorityModules = collect([
            'apartments',
            'properties',
            'reservations',
            'check-in',
            'invoices',
            'customers',
        ])->map(fn (string $slug) => AdminModules::find($slug))->filter()->values();

        $metrics = [
            [
                'label' => 'Admin modules',
                'value' => $modules->count(),
                'detail' => 'Mapped from myshortlet',
            ],
            [
                'label' => 'Property workflows',
                'value' => $this->sectionCount($sections, 'Properties'),
                'detail' => 'Booking operations',
            ],
            [
                'label' => 'Content areas',
                'value' => $this->sectionCount($sections, 'Content'),
                'detail' => 'Website management',
            ],
            [
                'label' => 'People areas',
                'value' => $this->sectionCount($sections, 'People'),
                'detail' => 'Users and guests',
            ],
        ];

        return view('admin.dashboard', compact('sections', 'modules', 'priorityModules', 'metrics'));
    }

    private function sectionCount(array $sections, string $sectionName): int
    {
        $section = collect($sections)->firstWhere('section', $sectionName);

        return count($section['items'] ?? []);
    }
}
