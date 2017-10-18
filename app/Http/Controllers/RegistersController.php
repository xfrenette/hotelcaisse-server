<?php

namespace App\Http\Controllers;

use App\Register;
use App\TransactionMode;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RegistersController extends Controller
{
    /**
     * Number of items per page in the paginated list screen
     * @type integer
     */
    const LIST_NB_PER_PAGE = 20;
    const CASH_FLOAT = 100;

    /**
     * Controller method for /registers
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function list(Request $request)
    {
        $dateFormat = __('filters.phpDateFormat');
        $timezone = $request->user()->timezone;
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        if ($startDate) {
            try {
                $startDate = Carbon::createFromFormat($dateFormat, $startDate, $timezone);
                $startDate->setTime(0, 0, 0, 0);
            } catch (\InvalidArgumentException $e) {
                $startDate = false;
            }
        }

        if ($endDate) {
            try {
                $endDate = Carbon::createFromFormat($dateFormat, $endDate, $timezone);
                $endDate->setTime(23, 59, 59, 999);
            } catch (\InvalidArgumentException $e) {
                $endDate = false;
            }
        }

        $query = $request->user()->currentTeam
            ->registers()
            ->with('calculatedValues')
            ->orderBy('opened_at', 'desc');

        if ($startDate) {
            $utcStartDate = $startDate->copy()->setTimezone('UTC');
            $query->where('opened_at', '>=', $utcStartDate);
        }

        if ($endDate) {
            $utcEndDate = $endDate->copy()->setTimezone('UTC');
            $query->where('opened_at', '<=', $utcEndDate);
        }

        $registers = $query->simplePaginate(self::LIST_NB_PER_PAGE)
            // Add parameters for paginator links
            ->appends($_GET);

        return view('registers.list', [
            'registers' => $registers,
            'cashFloat' => self::CASH_FLOAT,
            'startDate' => $startDate ? $startDate->format($dateFormat) : '',
            'endDate' => $endDate ? $endDate->format($dateFormat) : '',
        ]);
    }

    public function view(Request $request, Register $register)
    {
        $cashTotal = 0;
        $paymentsTotal = 0;
        $refundsTotal = 0;
        $openingCashError = $register->opening_cash - self::CASH_FLOAT;
        $register->transactions->each(
            function ($transaction) use (&$cashTotal, &$paymentsTotal, &$refundsTotal) {
                if ($transaction->transactionMode->type === TransactionMode::TYPE_CASH) {
                    $cashTotal += $transaction->amount;
                }

                if ($transaction->amount > 0) {
                    $paymentsTotal += $transaction->amount;
                } else {
                    $refundsTotal += $transaction->amount;
                }
            }
        );
        $transactionsTotal = $paymentsTotal + $refundsTotal;
        $cashMovementsTotal = $register->cashMovements->reduce(function ($total, $cashMovement) {
            return $total + $cashMovement->amount;
        }, 0);
        $declaredTotal = $register->closing_cash - $register->opening_cash + $register->post_amount;
        $netTotal = $transactionsTotal + $cashMovementsTotal;
        $cashTotal += $cashMovementsTotal + $openingCashError;
        $vars = [
            'register' => $register,
            'cashTotal' => $cashTotal,
            'paymentsTotal' => $paymentsTotal,
            'refundsTotal' => $refundsTotal,
            'transactionsTotal' => $transactionsTotal,
            'cashMovementsTotal' => $cashMovementsTotal,
            'netTotal' => $netTotal,
            'declaredTotal' => $declaredTotal,
            'registerError' => $netTotal - $declaredTotal,
            'cashError' => $register->closing_cash - $cashTotal,
        ];
        return view('registers.view', $vars);
    }
}
