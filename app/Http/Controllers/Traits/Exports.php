<?php


namespace App\Http\Controllers\Traits;


use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait Exports
{
    /**
     * @param $data array
     * @return \Illuminate\Http\Response
     */
    protected function downloadableCSV($data, $basename)
    {
        $csv = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');
        foreach ($data as $row) {
            fputcsv($csv, $row);
        }
        rewind($csv);
        $out = stream_get_contents($csv);

        $now = Carbon::now(Auth::user()->timezone);
        $fileName = $basename . '-' . $now->format('Y-m-d_H:i:s') . '.csv';

        return \response($out)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->header('Content-Length', strlen($out));
    }
}
