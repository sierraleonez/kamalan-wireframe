<?php

namespace App\Http\Middleware;

use App\Support\Account;
use Closure;
use Illuminate\Http\Request;

/** Halaman akun pengunjung hanya ada kalau ACCOUNTS=true. */
class AccountsEnabled
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(Account::enabled(), 404);

        return $next($request);
    }
}
