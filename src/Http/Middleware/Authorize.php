<?php

declare(strict_types=1);

namespace LaravelGoogleSheetI18n\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Authorization Middleware
 *
 * Controls access to the Translation Manager UI.
 */
class Authorize
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if Translation Manager is enabled
        if (!config('google-sheet-i18n.ui.enabled', true)) {
            abort(403, 'Translation Manager UI is disabled.');
        }

        // Check authorization callback
        $callback = config('google-sheet-i18n.ui.authorization');

        if ($callback && is_callable($callback)) {
            if (!call_user_func($callback, $request)) {
                abort(403, 'Unauthorized access to Translation Manager.');
            }
        }

        // Default: allow in local environment, deny in production
        if (!$callback && app()->environment('production')) {
            abort(403, 'Translation Manager is only available in local environment.');
        }

        return $next($request);
    }
}
