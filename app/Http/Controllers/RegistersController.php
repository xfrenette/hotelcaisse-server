<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\Exports;
use App\Http\Controllers\Traits\UsesFilters;
use App\Register;
use App\TransactionMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistersController extends Controller
{
    use UsesFilters;
    use Exports;

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

        $exportURL = route('registers.export', $_GET);

        return view('registers.list', [
            'registers' => $registers,
            'cashFloat' => self::CASH_FLOAT,
            'startDate' => $this->getFormattedStartDate(),
            'endDate' => $this->getFormattedEndDate(),
            'exportURL' => $exportURL,
        ]);
    }

    /**
     * Controller method for /registers/export
     *
     * @param $request Request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $query = $this->buildListQuery($request);

        $data = [];
        $columns = (array) __('registers.list.columns');

        // First row of titles
        $metaTitles = [];
        foreach ($columns as $column) {
            $metaTitles[] = self::stripTags($column['title']);
            for ($i = 0; $i < count($column) - 2; $i++) {
                $metaTitles[] = '';
            }
        }
        $data[] = $metaTitles;

        // Main titles
        $titles = [];
        foreach ($columns as $column) {
            foreach ($column as $key => $value) {
                if ($key !== 'title') {
                    $titles[] = self::stripTags($value);
                }
            }
        }
        $titles[] = 'URL détails';
        $data[] = $titles;

        // Rows
        $query->get()->each(function ($register) use (&$data) {
            $data[] = $this->getRegisterCSVData($register);
        });

        return $this->downloadableCSV($data, 'caisses');
    }

    public function view(Request $request, Register $register)
    {
        $cashTotal = 0;
        $postTotal = 0;
        $paymentsTotal = 0;
        $refundsTotal = 0;
        $openingCashError = $register->opening_cash - self::CASH_FLOAT;
        $register->transactions->each(
            function ($transaction) use (&$postTotal, &$cashTotal, &$paymentsTotal, &$refundsTotal) {
                if ($transaction->transactionMode->type === TransactionMode::TYPE_CASH) {
                    $cashTotal += $transaction->amount;
                } else {
                    $postTotal += $transaction->amount;
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
        $declaredTotal = $register->closing_cash + $register->post_amount;
        $netTotal = $transactionsTotal + $cashMovementsTotal;
        $cashTotal += $cashMovementsTotal + $openingCashError;
        $vars = [
            'cashFloat' => self::CASH_FLOAT,
            'register' => $register,
            'cashTotal' => $cashTotal,
            'postTotal' => $postTotal,
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

    protected function getRegisterCSVData(Register $register)
    {
        $opened = $register->state === Register::STATE_OPENED;
        $closed = !$opened;
        $openedAt = $register->opened_at->timezone(Auth::user()->timezone);
        $closedAt = $opened
            ? null
            : $register->closed_at->timezone(Auth::user()->timezone);
        $paymentsTotal = $register->getCalculatedValue(Register::PRE_CALC_PAYMENTS_TOTAL);
        $refundsTotal = -1 * $register->getCalculatedValue(Register::PRE_CALC_REFUNDS_TOTAL);
        $transactionsTotal = $paymentsTotal + $refundsTotal;
        $cashTransactionsTotal = $register->getCalculatedValue(Register::PRE_CALC_CASH_TX_TOTAL);
        $cashMovementsTotal = $register->getCalculatedValue(Register::PRE_CALC_CASH_MV_TOTAL);
        $openingCashError = $register->opening_cash - self::CASH_FLOAT;
        $cashExpected = $cashTransactionsTotal + $cashMovementsTotal + $openingCashError;
        $cashDeclared = $opened ? null : $register->closing_cash;
        $expectedPOSTAmount = $transactionsTotal - $cashTransactionsTotal;

        return [
            $register->number,
            $openedAt->toDateTimeString(),
            $register->employee,
            $register->opening_cash,
            $paymentsTotal,
            $refundsTotal,
            $transactionsTotal,
            $cashTransactionsTotal,
            $cashMovementsTotal,
            $openingCashError,
            $cashExpected,
            $closed ? $cashDeclared : '',
            $closed ? $register->post_ref : '',
            $expectedPOSTAmount,
            $closed ? $register->post_amount : '',
            $closed ? $closedAt->toDateTimeString() : '',
            route('registers.register.view', $register->id),
        ];
    }

    protected static function stripTags($str)
    {
        $str = preg_replace('#<br ?/?>#', ' ', $str);
        return strip_tags($str);
    }
}
