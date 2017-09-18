<?php

namespace App\Http\Controllers;

use App\Register;
use App\TransactionMode;
use Illuminate\Http\Request;

class RegistersController extends Controller
{
    /**
     * Controller method for /registers
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function list(Request $request)
    {
        $registers = $request->user()->currentTeam->registers()->orderBy('opened_at', 'desc')->get();
        return view('registers.list', ['registers' => $registers]);
    }

    public function view(Request $request, Register $register)
    {
        $cashTotal = 0;
        $paymentsTotal = 0;
        $refundsTotal = 0;
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
