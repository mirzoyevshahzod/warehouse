<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    //
    public function turkeyScraping()
    {
        return view('turkey-scraping');
    }

    public function belarusScraping()
    {
        return view('belarus-scraping');
    }

    public function eOmborScraping()
    {
        return view('e-ombor-scraping');
    }

    public function qozoqScraping()
    {
        return view('qozoq-scraping');
    }

    public function checkedQozoqScraping()
    {
        return view('checked-qozoq-scraping');
    }

    public function mintransScraping()
    {
        return view('mintrans-scraping');
    }

    public function eOmborATScraping()
    {
        return view('eombor.e-ombor-AT-scraping');
    }

    public function eOmborRWScraping()
    {
        return view('eombor.e-ombor-RW-scraping');
    }

    public function eOmborAVIAScraping()
    {
        return view('eombor.e-ombor-AVIA-scraping');
    }

    public function belarusBenyakoni()
    {
        return view('belarus.belarus-benyakoni');
    }

    public function belarusBrest()
    {
        return view('belarus.belarus-brest');
    }

    public function belarusGigorovschina()
    {
        return view('belarus.belarus-gigorovschina');
    }

    public function belarusKeminnii()
    {
        return view('belarus.belarus-keminnii-log');
    }

    public function belarusKozlovichi()
    {
        return view('belarus.belarus-kozlovichi');
    }

     public function login()
    {
        return view('login');
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    public function profile()
    {
        return view('profil');
    }

    public function importQozoqScraping()
    {
        return view('import-qozoq');
    }

     public function importEksportQozoqScraping()
    {
        return view('import-eksport-qozoq-scraping');
    }

    public function drivers()
    {
        return view('drivers');
    }


}
