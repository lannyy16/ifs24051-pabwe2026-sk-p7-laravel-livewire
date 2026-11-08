<?php

namespace App\Http\Middleware;

use App\Features\Stock\Models\Stock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStockOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $stock = $request->route('stock');

        if (! $stock instanceof Stock) {
            $stock = Stock::query()->findOrFail($stock);
        }

        $user = $request->user();

        if (
            ! $user ||
            (int) $stock->user_id !== (int) $user->getKey()
        ) {
            abort(403, 'Stok ini bukan milik Anda.');
        }

        return $next($request);
    }
}
