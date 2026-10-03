<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentSetting;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StaffBillingController extends Controller
{
    private const PAYMENT_STATUSES = ['unpaid', 'pending', 'approved', 'rejected'];

    public function index(Request $request): View
    {
        $paymentStatus = $request->query('payment_status');
        $query = Reservation::with('user')
            ->where(function ($query): void {
                $query->whereIn('status', ['verified', 'processing', 'released', 'completed'])
                    ->orWhereNotNull('payment_proof_path')
                    ->orWhereNotNull('payment_reference_id')
                    ->orWhereIn('payment_status', self::PAYMENT_STATUSES);
            })
            ->latest();

        if (in_array($paymentStatus, self::PAYMENT_STATUSES, true)) {
            $query->where('payment_status', $paymentStatus);
        }

        $reservations = $query->with('extensions')->paginate(20)->withQueryString();

        $invoiceRows = $reservations->getCollection()->map(function (Reservation $reservation): array {
            $vehicle = $reservation->vehicleUnit;
            $days = $reservation->rentalDays();
            $total = (float) $reservation->total_amount;
            if ($total <= 0) {
                $total = ($vehicle?->price ?? 0) * $days
                    + ($reservation->driver_option === 'with_driver' ? 1500 * $days : 0);
            }

            return [
                'reservationId' => $reservation->id,
                'invoiceType' => 'booking',
                'id' => $reservation->control_number,
                'source' => $reservation->booking_source === 'admin_staff' ? 'walkin' : 'online',
                'name' => $reservation->customer_name ?: ($reservation->user?->name ?? 'Walk-in Customer'),
                'phone' => $reservation->customer_phone ?: ($reservation->user?->contact_number ?? 'No contact'),
                'vehicle' => $reservation->vehicle,
                'vehiclePlate' => $vehicle?->plate,
                'total' => $total,
                'paid' => (float) $reservation->paid_amount,
                'submittedAmount' => (float) $reservation->payment_submitted_amount,
                'verificationPending' => $reservation->payment_status === 'pending'
                    && ((float) $reservation->payment_submitted_amount > 0
                        || ((float) $reservation->paid_amount > 0 && ($reservation->payment_proof_path || $reservation->payment_reference_id))),
                'ref' => $reservation->payment_reference_id ?: 'No reference',
                'proof' => $this->uploadedFileUrl($reservation->payment_proof_path),
                'extensionPayments' => $reservation->extensions->map(fn ($extension) => [
                    'id' => $extension->id,
                    'control' => $reservation->control_number,
                    'days' => (int) $extension->days,
                    'amount' => (float) $extension->amount,
                    'paidAmount' => (float) $extension->paid_amount,
                    'balance' => max((float) $extension->amount - (float) $extension->paid_amount, 0),
                    'submittedAmount' => (float) $extension->payment_submitted_amount,
                    'status' => $extension->status,
                    'reference' => $extension->payment_reference_id ?: 'No reference',
                    'proof' => $this->uploadedFileUrl($extension->payment_proof_path),
                    'date' => $extension->created_at?->format('M j, Y'),
                    'paymentActor' => AuditLog::with('actor')
                        ->where('entity_type', \App\Models\RentalExtension::class)
                        ->where('entity_id', $extension->id)
                        ->where('action', 'rental_extension.payment_confirmed')
                        ->latest()
                        ->get()
                        ->map(fn ($log) => [
                            'name' => $log->actor?->name ?: 'System',
                            'role' => $log->actor?->role === 'admin' ? 'Admin' : ($log->actor?->role === 'staff' ? 'Staff' : 'System'),
                            'action' => 'Extension payment confirmed',
                            'amount' => $log->metadata['amount'] ?? null,
                            'date' => $log->created_at?->format('M j, Y g:i A'),
                        ])->values()->all(),
                ])->values()->all(),
                'method' => ucfirst((string) $reservation->payment_mode),
                'status' => (float) $reservation->paid_amount >= $total ? 'approved' : $reservation->payment_status,
                'paymentActor' => AuditLog::with('actor')
                    ->where('entity_type', Reservation::class)
                    ->where('entity_id', $reservation->id)
                    ->whereIn('action', ['reservation.payment_added', 'reservation.payment_status_updated'])
                    ->latest()
                    ->get()
                    ->map(fn ($log) => [
                        'name' => $log->actor?->name ?: 'System',
                        'role' => $log->actor?->role === 'admin' ? 'Admin' : ($log->actor?->role === 'staff' ? 'Staff' : 'System'),
                        'action' => $log->action === 'reservation.payment_added' ? 'Payment added' : 'Payment status updated',
                        'amount' => $log->metadata['amount'] ?? null,
                        'date' => $log->created_at?->format('M j, Y g:i A'),
                    ])->values()->all(),
            ];
        })->values()->all();

        return view('staff.billing', [
            'reservations' => $reservations,
            'invoiceRows' => $invoiceRows,
            'paymentStatus' => $paymentStatus,
            'paymentStatuses' => self::PAYMENT_STATUSES,
            'paymentSettings' => PaymentSetting::current(),
        ]);
    }

    private function uploadedFileUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url(ltrim($path, '/')) : null;
    }
}
