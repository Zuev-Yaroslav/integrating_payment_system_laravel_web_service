<?php

namespace App\Services\Security;

class IpAddressChecker
{
    public static function ipInSubnet(string $ip, string $subnet): bool
    {
        if (!str_contains($subnet, '/')) {
            $subnet .= str_contains($subnet, ':') ? '/128' : '/32';
        }

        [$subnetIp, $mask] = explode('/', $subnet);
        $mask = (int)$mask;

        // Определяем тип протокола (IPv4 или IPv6)
        $ipPacked = inet_pton($ip);
        $subnetPacked = inet_pton($subnetIp);

        // Если IP-адрес поврежден или имеет неверный формат
        if ($ipPacked === false || $subnetPacked === false) {
            return false;
        }

        // Защита от коллизий: IPv4 не может проверяться по IPv6 маске и наоборот
        if (strlen($ipPacked) !== strlen($subnetPacked)) {
            return false;
        }

        // Побитовое сравнение бинарных строк (сеньор-подход, одинаково работающий для IPv4 и IPv6)
        $ipBits = self::ipToBitString($ipPacked);
        $subnetBits = self::ipToBitString($subnetPacked);

        return substr($ipBits, 0, $mask) === substr($subnetBits, 0, $mask);
    }

    private static function ipToBitString(string $packedIp): string
    {
        $bits = '';
        $chars = str_split($packedIp);
        foreach ($chars as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        return $bits;
    }
}
