<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use YooKassa\Helpers\NotificationHelper;

class YookassaIpWhitelist
{
    protected array $trustedSubnets = [
        '185.71.76.0/27',
        '185.71.77.0/27',
        '77.75.153.0/25',
        '77.75.156.11',
        '77.75.156.35',
        '77.75.154.128/25',
        '2a02:5180::/32',
    ];
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();

        if (app()->environment('local')) {
            return $next($request);
        }

        foreach ($this->trustedSubnets as $subnet) {
            if ($this->ipInSubnet($clientIp, $subnet)) {
                return $next($request);
            }
        }

        Log::channel('payments')->alert("ПОПЫТКА ВЗЛОМА: Фейковый вебхук с недоверенного IP: {$clientIp}");

        return response()->json(['message' => 'Unauthorized IP'], 403);
    }

    private function ipInSubnet(string $ip, string $subnet): bool
    {
        if (str_contains($ip, ':') || str_contains($subnet, ':')) {
            return false;
        }

        [$subnetIp, $mask] = explode('/', $subnet);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnetIp);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
