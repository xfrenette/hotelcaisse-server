<?php

namespace App\Http\Controllers\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait UsesFilters
{
    protected $startDate = null;
    protected $endDate = null;

    /**
     * @param $query \Illuminate\Database\Query\Builder
     * @param $request Request
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function filterQuery($query, Request $request)
    {
        $dateFormat = $this->getDateFilterFormat();
        $timezone = Auth::user()->timezone;
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        if ($startDate) {
            try {
                $startDate = Carbon::createFromFormat($dateFormat, $startDate, $timezone)
                    ->setTime(0, 0, 0, 0)
                    ->setTimezone('UTC');
            } catch (\InvalidArgumentException $e) {
                $startDate = null;
            }
        }

        if ($endDate) {
            try {
                $endDate = Carbon::createFromFormat($dateFormat, $endDate, $timezone)
                    ->setTime(23, 59, 59, 999)
                    ->setTimezone('UTC');
            } catch (\InvalidArgumentException $e) {
                $endDate = null;
            }
        }

        if ($startDate) {
            $query = $this->filterWithStartDate($query, $startDate);
        }

        if ($endDate) {
            $query = $this->filterWithEndDate($query, $endDate);
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate;

        return $query;
    }

    /**
     * $startDate is in UTC
     * @param $query \Illuminate\Database\Query\Builder
     * @param $startDate \Carbon\Carbon
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function filterWithStartDate($query, $startDate)
    {
        return $query;
    }

    /**
     * $endDate is in UTC
     * @param $query \Illuminate\Database\Query\Builder
     * @param $endDate \Carbon\Carbon
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function filterWithEndDate($query, $endDate)
    {
        return $query;
    }

    protected function getDateFilterFormat()
    {
        return __('filters.phpDateFormat');
    }

    protected function getFormattedStartDate()
    {
        $dateFormat = $this->getDateFilterFormat();
        $timezone = Auth::user()->timezone;
        return $this->startDate
            ? $this->startDate->copy()->setTimezone($timezone)->format($dateFormat)
            : '';
    }

    protected function getFormattedEndDate()
    {
        $dateFormat = $this->getDateFilterFormat();
        $timezone = Auth::user()->timezone;
        return $this->endDate
            ? $this->endDate->copy()->setTimezone($timezone)->format($dateFormat)
            : '';
    }
}
