<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\UsesFilters;
use App\Register;
use App\TransactionMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistersController extends Controller
{
    use UsesFilters;

    /**
     * Number of items per page in the paginated list screen
     * @type integer
     */
    const LIST_NB_PER_PAGE = 20;
    const CASH_FLOAT = 100;

    /**
     * Controller method for /registers
     *
     * @param $request Request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function list(Request $request)
    {
        $query = $this->buildListQuery($request);

        $registers = $query->simplePaginate(self::LIST_NB_PER_PAGE)
            // Add parameters for paginator links
            ->appends($_GET);

        return view('registers.list', [
            'registers' => $registers,
            'cashFloat' => self::CASH_FLOAT,
            'startDate' => $this->getFormattedStartDate(),
            'endDate' => $this->getFormattedEndDate(),
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

    /**
     * @param $request Request
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildListQuery(Request $request)
    {
        $query = Auth::user()->currentTeam
            ->registers()
            ->with('calculatedValues')
            ->orderBy('opened_at', 'desc');

        return $this->filterQuery($query, $request);
    }

    protected function filterWithStartDate($query, $startDate)
    {
        return $query->where('opened_at', '>=', $startDate);
    }

    protected function filterWithEndDate($query, $endDate)
    {
        return $query->where('opened_at', '<=', $endDate);
    }
}
