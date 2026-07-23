<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function index(Request $request)
    {
        return view('user.credits.index', [
            'balance' => (int) $request->user()->credits,
            'transactions' => $request->user()->creditTransactions()->orderByDesc('id')->paginate(25),
        ]);
    }
}
