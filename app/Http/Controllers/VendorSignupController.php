<?php

namespace App\Http\Controllers;

use App\Catalog\Catalog;
use App\Catalog\Pages;
use App\Models\VendorApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Form untuk vendor yang ingin masuk katalog (/daftarkan-usaha). */
class VendorSignupController extends Controller
{
    public function form(): Response
    {
        return response()->view('pages.vendor-form', ['p' => Pages::vendorForm()]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $request->merge(['wa' => preg_replace('/[\s.\-]/', '', (string) $request->input('wa'))]);

        $categories = array_keys(Catalog::data()['categories']);
        $areas = array_keys(Catalog::areas());

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'contact_name' => ['required', 'string', 'max:80'],
            'wa' => ['required', 'regex:/^(\+?62|0)8\d{7,11}$/'],
            'category' => ['required', 'in:'.implode(',', $categories).',lainnya'],
            'area' => ['required', 'in:'.implode(',', $areas).',lainnya'],
        ], [
            'business_name.required' => 'Isi nama usaha.',
            'contact_name.required' => 'Isi nama kontak.',
            'wa.required' => 'Isi nomor WhatsApp.',
            'wa.regex' => 'Isi nomor WhatsApp yang diawali 08 atau 62, 10–13 digit.',
            'category.required' => 'Pilih kategori usaha.',
            'category.in' => 'Pilih kategori usaha.',
            'area.required' => 'Pilih area.',
            'area.in' => 'Pilih area.',
        ]);

        VendorApplication::create($data);

        return redirect('/daftarkan-usaha/terkirim')->with('vendor_sent', ['name' => $data['business_name'], 'wa' => $data['wa']]);
    }

    public function done(Request $request): Response
    {
        return response()->view('pages.vendor-done', ['p' => Pages::vendorDone($request->session()->get('vendor_sent'))]);
    }
}
