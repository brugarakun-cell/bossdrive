<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentSetting;
use App\Models\RentalExtension;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $reservations = $request->user()->reservations()
            ->with(['extensions.reservation', 'vehicleUnit'])
            ->latest()
            ->get();
        $reservationApprovals = AuditLog::with('actor')
            ->where('entity_type', Reservation::class)
            ->whereIn('entity_id', $reservations->pluck('id'))
            ->whereIn('action', ['reservation.payment_added', 'reservation.payment_status_updated'])
            ->latest()
            ->get()
            ->filter(fn ($log) => $log->action === 'reservation.payment_added'
                || ($log->metadata['payment_status'] ?? null) === 'approved')
            ->groupBy('entity_id')
            ->map(fn ($logs) => $logs->map(fn ($log) => [
                'name' => $log->actor?->name ?: 'Unknown',
                'role' => $this->actorRole($log->actor?->role),
                'amount' => $log->metadata['amount'] ?? null,
                'date' => $log->created_at?->format('M d, Y g:i A'),
            ])->values());
        $extensions = $reservations->flatMap->extensions;
        $extensionApprovals = AuditLog::with('actor')
            ->where('entity_type', RentalExtension::class)
            ->whereIn('entity_id', $extensions->pluck('id'))
            ->where('action', 'rental_extension.payment_confirmed')
            ->latest()
            ->get()
            ->groupBy('entity_id')
            ->map(fn ($logs) => $logs->map(fn ($log) => [
                'name' => $log->actor?->name ?: 'Unknown',
                'role' => $this->actorRole($log->actor?->role),
                'amount' => $log->metadata['amount'] ?? null,
                'date' => $log->created_at?->format('M d, Y g:i A'),
            ])->values());
        $paymentTargets = $reservations->map(function ($reservation): array {
            $bookingBalance = max($this->bookingTotal($reservation) - (float) $reservation->paid_amount, 0);
            $bookingWaiting = $reservation->payment_status === 'pending'
                && ((float) $reservation->payment_submitted_amount > 0
                    || $reservation->payment_proof_path || $reservation->payment_reference_id);
            $extension = null;

            if ($bookingBalance <= 0 && ! in_array($reservation->status, ['cancelled', 'void'], true)) {
                $extension = $reservation->extensions
                    ->sortBy('created_at')
                    ->first(fn ($item) => in_array($item->status, ['approved', 'payment_pending'], true)
                        && (float) $item->amount > (float) $item->paid_amount);
            }

            $extensionBalance = $extension
                ? max((float) $extension->amount - (float) $extension->paid_amount, 0)
                : 0;
            $extensionWaiting = $extension && $extension->status === 'payment_pending'
                && ($extension->payment_proof_path || $extension->payment_reference_id);

            return [
                'controlNumber' => $reservation->control_number,
                'reservationId' => $reservation->id,
                'bookingBalance' => $bookingBalance,
                'bookingWaiting' => (bool) $bookingWaiting,
                'extensionId' => $extension?->id,
                'extensionBalance' => $extensionBalance,
                'extensionWaiting' => (bool) $extensionWaiting,
                'cancelled' => in_array($reservation->status, ['cancelled', 'void'], true),
            ];
        })->values();

        return view('user.payment', [
            'user' => $request->user(),
            'reservations' => $reservations,
            'reservation' => $reservations->first(),
            'paymentSettings' => PaymentSetting::current(),
            'paymentTargets' => $paymentTargets,
            'reservationApprovals' => $reservationApprovals,
            'extensionApprovals' => $extensionApprovals,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'reference_id' => trim((string) $request->input('reference_id')) ?: null,
        ]);

        $data = $request->validate([
            'control_number' => ['required', 'string', 'max:100'],
            'reservation_id' => ['nullable', 'integer'],
            'extension_id' => ['nullable', 'integer'],
            'payment_amount' => ['required', 'numeric', 'gt:0'],
            'payment_proof' => ['nullable', 'required_without:reference_id', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'reference_id' => ['nullable', 'required_without:payment_proof', 'string', 'max:32'],
        ]);

        $data['control_number'] = strtoupper(trim($data['control_number']));
        $reservation = $request->user()->reservations()
            ->with('vehicleUnit')
            ->whereRaw('UPPER(control_number) = ?', [$data['control_number']])
            ->firstOrFail();
        abort_if(
            ! empty($data['reservation_id']) && (int) $data['reservation_id'] !== $reservation->id,
            422,
            'The control number does not match the selected booking.'
        );

        if (! empty($data['extension_id'])) {
            $extension = $reservation->extensions()->whereKey($data['extension_id'])
                ->whereIn('status', ['approved', 'payment_pending'])
                ->firstOrFail();
            abort_if(in_array($reservation->status, ['cancelled', 'void'], true), 422, 'Cancelled bookings cannot receive payments.');
            abort_if(
                $this->bookingTotal($reservation) > (float) $reservation->paid_amount,
                422,
                'Pay the original booking balance before paying its extension.'
            );
            $extensionBalance = max((float) $extension->amount - (float) $extension->paid_amount, 0);
            if ((float) $data['payment_amount'] > $extensionBalance) {
                throw ValidationException::withMessages([
                    'payment_amount' => 'The submitted amount cannot exceed the remaining extension balance.',
                ]);
            }
            $oldProof = $extension->payment_proof_path;
            $proofPath = $request->hasFile('payment_proof')
                ? $request->file('payment_proof')->store('reservations/extensions', 'public')
                : null;
            if ($request->hasFile('payment_proof')
                && (! is_string($proofPath) || ! Storage::disk('public')->exists($proofPath))) {
                report(new RuntimeException('Unable to store extension payment screenshot.'));

                throw ValidationException::withMessages([
                    'payment_proof' => 'The payment screenshot could not be saved. Please try again or contact support.',
                ]);
            }
            $extension->update([
                'payment_proof_path' => $proofPath,
                'payment_reference_id' => $data['reference_id'] ?? null,
                'payment_submitted_amount' => $data['payment_amount'],
                'status' => 'payment_pending',
            ]);
            if ($oldProof && $oldProof !== $proofPath) {
                Storage::disk('public')->delete($oldProof);
            }

            return back()->with('success', 'Extension payment proof submitted successfully.');
        }

        abort_if(in_array($reservation->status, ['cancelled', 'void'], true), 422, 'Cancelled bookings cannot receive payments.');
        $bookingTotal = $this->bookingTotal($reservation);
        $bookingBalance = max($bookingTotal - (float) $reservation->paid_amount, 0);
        if ((float) $data['payment_amount'] > $bookingBalance) {
            throw ValidationException::withMessages([
                'payment_amount' => 'The submitted amount cannot exceed the booking balance.',
            ]);
        }
        $oldProof = $reservation->payment_proof_path;
        $proofPath = $request->hasFile('payment_proof')
            ? $request->file('payment_proof')->store('reservations/payments', 'public')
            : null;
        if ($request->hasFile('payment_proof')
            && (! is_string($proofPath) || ! Storage::disk('public')->exists($proofPath))) {
            report(new RuntimeException('Unable to store booking payment screenshot.'));

            throw ValidationException::withMessages([
                'payment_proof' => 'The payment screenshot could not be saved. Please try again or contact support.',
            ]);
        }

        $reservation->update([
            'payment_proof_path' => $proofPath,
            'payment_reference_id' => $data['reference_id'] ?? null,
            'payment_submitted_amount' => $data['payment_amount'],
            'payment_status' => 'pending',
        ]);

        if ($oldProof && $oldProof !== $proofPath) {
            Storage::disk('public')->delete($oldProof);
        }

        return back()->with('success', 'Payment proof submitted successfully. It is now waiting for verification.');
    }

    private function bookingTotal(Reservation $reservation): float
    {
        $bookingTotal = (float) $reservation->total_amount;
        if ($bookingTotal > 0) {
            return $bookingTotal;
        }

        $days = $reservation->rentalDays();

        return (float) ($reservation->vehicleUnit?->price ?? 0) * $days
            + ($reservation->driver_option === 'with_driver' ? 1500 * $days : 0);
    }

    private function actorRole(?string $role): string
    {
        return match ($role) {
            'admin' => 'Admin',
            'staff' => 'Staff',
            default => 'Account',
        };
    }
}
