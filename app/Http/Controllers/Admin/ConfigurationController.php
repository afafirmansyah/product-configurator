<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConfigurationSession;
use Illuminate\Contracts\View\View;

class ConfigurationController extends Controller
{
    public function index(): View
    {
        $configurations = ConfigurationSession::with(['product', 'user'])->latest()->paginate(20);

        return view('admin.configurations.index', compact('configurations'));
    }
}
