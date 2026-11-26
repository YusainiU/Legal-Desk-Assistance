<?php

namespace App\Http\Controllers;

use App\Models\Matter;
use App\Support\CaseBilling;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseTimeChargeExportController
{
    public function show(Request $request, int $matter): StreamedResponse
    {
        $case = Matter::query()->findOrFail($matter);
        \Illuminate\Support\Facades\Gate::authorize('view', $case);
        $stages = config('matterdesk.stages');

        return response()->streamDownload(function () use ($case, $stages) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Stage', 'Work performed', 'Minutes', 'Hourly rate', 'Charge', 'Currency'], escape: '');

            $minutes = 0;
            $amount = 0;

            foreach ($case->timeCharges()->orderBy('stage')->orderBy('id')->cursor() as $charge) {
                fputcsv($output, [
                    $this->spreadsheetText($stages[$charge->stage][0] ?? 'Recorded stage'),
                    $this->spreadsheetText($charge->description),
                    $charge->minutes,
                    CaseBilling::decimal($charge->hourly_rate_minor),
                    CaseBilling::decimal($charge->amount_minor),
                    $case->billing_currency,
                ], escape: '');
                $minutes += $charge->minutes;
                $amount += $charge->amount_minor;
            }

            fputcsv($output, ['Total', '', $minutes, '', CaseBilling::decimal($amount), $case->billing_currency], escape: '');
            fclose($output);
        }, 'MD-'.$case->id.'-time-charges.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function spreadsheetText(string $value): string
    {
        return preg_match('/\A(?:[\t\r\n]|\s*[=+\-@＝＋－＠])/u', $value) ? "'".$value : $value;
    }
}
