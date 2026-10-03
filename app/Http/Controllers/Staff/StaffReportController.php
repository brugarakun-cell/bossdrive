<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use App\Models\PickupConditionReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StaffReportController extends Controller
{
    public function index(): View
    {
        $inquiries = ContactInquiry::latest()->get();
        $pickupReports = PickupConditionReport::with(['user', 'reservation'])
            ->latest()
            ->get();

        return view('staff.reports', compact('inquiries', 'pickupReports'));
    }

    public function destroyInquiry(ContactInquiry $inquiry): RedirectResponse|JsonResponse
    {
        $inquiry->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Inquiry deleted.']);
        }

        return back()->with('success', 'Inquiry deleted.');
    }

    public function destroyPickupReport(PickupConditionReport $report): RedirectResponse|JsonResponse
    {
        if ($report->photo_path) {
            Storage::disk('public')->delete($report->photo_path);
        }

        $report->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Pickup condition report deleted.']);
        }

        return back()->with('success', 'Pickup condition report deleted.');
    }
}
