<?php

namespace App\Http\Controllers;

use App\Services\Installer\InstallerAccessService;
use Illuminate\Http\Request;

class InstallerAccessController extends Controller
{
    public function index(InstallerAccessService $access)
    {
        $access->token();
        return view('installer.access');
    }

    public function verify(Request $request, InstallerAccessService $access)
    {
        $code = $request->input('setup_code');
        if (!is_string($code) || strlen($code) > 256 || !hash_equals($access->token(), trim($code))) {
            return back()->withErrors(['setup_code' => 'The server setup code is incorrect. Read it from your own hosting files and try again.']);
        }
        $request->session()->regenerate();
        $request->session()->put('installer.owner_proof', $access->proof());
        return redirect()->route('install.welcome');
    }
}
