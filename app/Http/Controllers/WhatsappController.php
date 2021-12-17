<?php

namespace App\Http\Controllers;

use Auth;

class WhatsappController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->canAccessModule("whatsapp-sender")) {
            abort(403);
        }
        return view("admin.whatsapp.index");
    }
}
