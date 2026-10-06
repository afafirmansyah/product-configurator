<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'app_name'    => config('app.name'),
            'app_url'     => config('app.url'),
            'default_currency' => 'EUR',
            'quotation_validity_days' => 30,
        ];
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        // In a real app, persist to a settings table or .env
        return back()->with('success', 'Settings saved.');
    }
}
