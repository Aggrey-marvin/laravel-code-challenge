<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE
        ]);

        // Calculate repayment amount per term
        $repaymentAmount = intdiv($amount, $terms);
        $remainder = $amount % $terms;

        $startDate = \Carbon\Carbon::parse($processedAt);

        for ($i = 0; $i < $terms; $i++) {
            // Distribute remainder to the first repayment(s)
            $amountForThisTerm = $repaymentAmount + ($i === $terms - 1 ? $remainder : 0);

            $dueDate = $startDate->copy()->addMonths($i+1);

            $loan->scheduledRepayments()->create([
                'due_date' => $dueDate->format('Y-m-d'),
                'amount' => $amountForThisTerm,
                'outstanding_amount' => $amountForThisTerm,
                'status' => \App\Models\ScheduledRepayment::STATUS_DUE,
                'currency_code' => $currencyCode
            ]);
        }

        return $loan; 
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        
         $remainingAmount = $amount;

        // Get all due or partial scheduled repayments in ascending order
        $scheduledRepayments = $loan->scheduledRepayments()
            ->whereIn('status', [ScheduledRepayment::STATUS_DUE, ScheduledRepayment::STATUS_PARTIAL])
            ->orderBy('id', 'asc')
            ->get();

        foreach ($scheduledRepayments as $scheduledRepayment) {
            if ($remainingAmount <= 0) break;

            $toRepay = min($scheduledRepayment->outstanding_amount, $remainingAmount);

            $scheduledRepayment->outstanding_amount -= $toRepay;
            $scheduledRepayment->status = $scheduledRepayment->outstanding_amount == 0
                ? ScheduledRepayment::STATUS_REPAID
                : ScheduledRepayment::STATUS_PARTIAL;
            $scheduledRepayment->save();

            $remainingAmount -= $toRepay;
        }

        // Update loan outstanding amount and status
        $loan->outstanding_amount = $loan->scheduledRepayments()->sum('outstanding_amount');
        $loan->status = $loan->outstanding_amount == 0 ? Loan::STATUS_REPAID : Loan::STATUS_DUE;
        $loan->save();

        // Record the received repayment
        $received_payment = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt
        ]);

        return $received_payment;
    }
}
