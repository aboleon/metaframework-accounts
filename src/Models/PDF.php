<?php

namespace MetaFramework\Accounts\Models;

use Barryvdh\DomPDF\Facade\Pdf as Printer;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Model;
use MetaFramework\Traits\Locale;

class PDF extends Model
{
    use Locale;

    public $timestamps = false;

    public static function show($hash = null)
    {
        $data = Invoice::where('hash', $hash)->with(['details', 'client'])->first();

        if (is_null($data)) {
            abort(404);
        }

        $locale = $data->pdf_locale;
        if (empty($locale)) {
            $locale = app()->getLocale();
        } elseif (is_array(config('app.languages')) && ! in_array($locale, config('app.languages'))) {
            $locale = app()->getLocale();
        }

        app()->setLocale($locale);

        $view = 'mfw-accounts::invoices.pdf_'.$locale;

        if (view()->exists($view)) {
            $pdf_data = [
                'data'          => $data,
                'company'       => Company::info($locale),
                'bank_accounts' => BankAccounts::accounts($locale),
                'locale'        => $locale,
            ];

           // $fontDir = storage_path('fonts');

            return Printer::loadView($view, $pdf_data)
                ->setOption('printBackground', true)
                ->setOption('isRemoteEnabled', true)
         /*       ->setOption('fontDir', $fontDir)
                ->setOption('fontCache', $fontDir)
                ->setOption('tempDir', sys_get_temp_dir())
                ->setOption('chroot', $fontDir)
         */
                ->stream();
        }
    }
}
