<?php

use App\Models\RentalExtension;
use App\Models\Reservation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rental_extensions')
            ->select('reservation_id', DB::raw('SUM(amount) as extension_total'))
            ->groupBy('reservation_id')
            ->orderBy('reservation_id')
            ->get()
            ->each(function (object $extensionTotal): void {
                $reservation = DB::table('reservations')
                    ->where('id', $extensionTotal->reservation_id)
                    ->first(['total_amount', 'paid_amount', 'payment_status']);

                if (! $reservation) {
                    return;
                }

                $bookingTotal = max(
                    (float) $reservation->total_amount - (float) $extensionTotal->extension_total,
                    0
                );
                $bookingPaid = min((float) $reservation->paid_amount, $bookingTotal);
                $updates = [
                    'total_amount' => $bookingTotal,
                    'paid_amount' => $bookingPaid,
                ];

                if (
                    $reservation->payment_status === 'pending'
                    && $bookingPaid >= $bookingTotal
                ) {
                    $updates['payment_status'] = 'approved';
                }

                DB::table('reservations')
                    ->where('id', $extensionTotal->reservation_id)
                    ->update($updates);

                $excessPaid = max((float) $reservation->paid_amount - $bookingTotal, 0);
                if ($excessPaid <= 0) {
                    return;
                }

                $paymentLogs = DB::table('audit_logs')
                    ->where('entity_type', Reservation::class)
                    ->where('entity_id', $extensionTotal->reservation_id)
                    ->where('action', 'reservation.payment_added')
                    ->orderBy('created_at')
                    ->get(['actor_user_id', 'metadata', 'created_at']);
                $paymentCredits = collect();
                $previousPaid = 0.0;
                foreach ($paymentLogs as $log) {
                    $metadata = json_decode((string) $log->metadata, true) ?: [];
                    $paidAfter = (float) ($metadata['paid_amount'] ?? 0);
                    $credit = max($paidAfter - $bookingTotal, 0)
                        - max($previousPaid - $bookingTotal, 0);
                    $previousPaid = $paidAfter;

                    if ($credit > 0) {
                        $paymentCredits->push([
                            'actor_user_id' => $log->actor_user_id,
                            'amount' => $credit,
                            'created_at' => $log->created_at,
                        ]);
                    }
                }
                $creditTotal = (float) $paymentCredits->sum('amount');
                $canRecordApprovals = abs($creditTotal - $excessPaid) < 0.01;
                $extensions = DB::table('rental_extensions')
                    ->where('reservation_id', $extensionTotal->reservation_id)
                    ->orderBy('id')
                    ->get(['id', 'amount', 'paid_amount', 'status']);
                $creditIndex = 0;
                $creditRemaining = (float) ($paymentCredits[$creditIndex]['amount'] ?? 0);

                foreach ($extensions as $extension) {
                    if ($excessPaid <= 0) {
                        break;
                    }

                    $extensionBalance = max((float) $extension->amount - (float) $extension->paid_amount, 0);
                    $allocation = min($excessPaid, $extensionBalance);
                    if ($allocation <= 0) {
                        continue;
                    }

                    $extensionPaid = (float) $extension->paid_amount + $allocation;
                    $recordedPaidAmount = (float) $extension->paid_amount;
                    $extensionStatus = $extensionPaid >= (float) $extension->amount
                        ? 'paid'
                        : ($extension->status === 'payment_pending' ? 'payment_pending' : 'approved');

                    DB::table('rental_extensions')
                        ->where('id', $extension->id)
                        ->update([
                            'paid_amount' => $extensionPaid,
                            'status' => $extensionStatus,
                        ]);

                    while ($canRecordApprovals && $allocation > 0 && $creditRemaining > 0) {
                        $allocatedCredit = min($allocation, $creditRemaining);
                        if ($allocatedCredit > 0) {
                            $recordedPaidAmount += $allocatedCredit;
                            DB::table('audit_logs')->insert([
                                'actor_user_id' => $paymentCredits[$creditIndex]['actor_user_id'],
                                'action' => 'rental_extension.payment_confirmed',
                                'entity_type' => RentalExtension::class,
                                'entity_id' => $extension->id,
                                'metadata' => json_encode([
                                    'amount' => $allocatedCredit,
                                    'paid_amount' => $recordedPaidAmount,
                                    'balance' => max((float) $extension->amount - $recordedPaidAmount, 0),
                                    'status' => $recordedPaidAmount >= (float) $extension->amount ? 'paid' : $extensionStatus,
                                    'migrated_from_booking_payment' => true,
                                ]),
                                'created_at' => $paymentCredits[$creditIndex]['created_at'],
                                'updated_at' => $paymentCredits[$creditIndex]['created_at'],
                            ]);
                            $allocation -= $allocatedCredit;
                            $creditRemaining -= $allocatedCredit;
                        }

                        if ($creditRemaining <= 0) {
                            $creditIndex++;
                            $creditRemaining = (float) ($paymentCredits[$creditIndex]['amount'] ?? 0);
                        }
                    }

                    $excessPaid -= min($excessPaid, $extensionBalance);
                }
            });
    }

    public function down(): void
    {
        // Extension charges are kept separately and cannot safely be merged back into booking totals.
    }
};
