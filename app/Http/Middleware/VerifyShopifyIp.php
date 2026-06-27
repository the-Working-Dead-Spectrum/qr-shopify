<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de vérification d'IP Shopify.
 *
 * Shopify publie une liste officielle d'IPs sources pour ses webhooks :
 * https://shopify.dev/docs/api/webhooks#known-ip-addresses
 *
 * Activable via SHOPIFY_IP_VERIFICATION=true.
 *
 * Modes :
 *  - strict    : rejet immédiat (401) si IP hors liste
 *  - permissive: log WARNING + requête acceptée (HMAC reste la sécurité)
 *
 * La sécurité principale reste la vérification HMAC : ce middleware est
 * un second rempart, jamais le seul.
 *
 * Listes d'IPs mises à jour selon la documentation Shopify au 2026-01.
 * ⚠️ Shopify peut modifier ses IPs — voir commande artisan shopify:refresh-ip-list.
 */
class VerifyShopifyIp
{
    /**
     * Liste officielle des IPs Shopify (IPv4 + IPv6).
     * Sources : https://shopify.dev/docs/api/webhooks#known-ip-addresses
     *              + https://help.shopify.com/en/manual/intro-to-shopify/important-addresses
     *
     * @var array<int, string>
     */
    private const SHOPIFY_IPS_V4 = [
        '23.227.32.0/19',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '108.162.192.0/18',
        '131.0.72.0/22',
        '141.101.64.0/18',
        '162.158.0.0/15',
        '172.64.0.0/13',
        '173.245.48.0/20',
        '188.114.96.0/20',
        '190.93.240.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.159.0.0/16',
    ];

    /** @var array<int, string> */
    private const SHOPIFY_IPS_V6 = [
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $enabled = (bool) config('shopify.ip_verification.enabled', false);

        if (! $enabled) {
            return $next($request);
        }

        $clientIp = $request->ip();
        $isShopifyIp = $this->isShopifyIp($clientIp);

        if ($isShopifyIp) {
            return $next($request);
        }

        $strict = (bool) config('shopify.ip_verification.strict', false);

        if ($strict) {
            Log::critical('[shopify.ip] rejected_non_shopify_ip', [
                'ip' => $clientIp,
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
            ]);

            return response()->json(
                ['error' => 'Request from non-Shopify IP rejected'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        // Mode permissif : on log mais on laisse passer (HMAC = sécurité principale).
        Log::warning('[shopify.ip] non_shopify_ip_detected', [
            'ip' => $clientIp,
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
        ]);

        return $next($request);
    }

    /**
     * Vérifie si une IP appartient aux ranges Shopify.
     */
    private function isShopifyIp(string $ip): bool
    {
        if ($ip === '' || $ip === null) {
            return false;
        }

        $ranges = self::SHOPIFY_IPS_V4;

        if ((bool) config('shopify.ip_verification.allow_ipv6', true)) {
            $ranges = array_merge($ranges, self::SHOPIFY_IPS_V6);
        }

        foreach ($ranges as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si une IP appartient à un range CIDR.
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr, 2);

        // Conversion IP en entier long
        $ipLong = $this->ipToLong($ip);
        $subnetLong = $this->ipToLong($subnet);

        if ($ipLong === false || $subnetLong === false) {
            // Probablement IPv6 ou format invalide
            return false;
        }

        $mask = -1 << (32 - (int) $bits);
        $mask &= 0xFFFFFFFF;

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    /**
     * Convertit une IP en entier long (supporte IPv4 et IPv6 partielle).
     */
    private function ipToLong(string $ip): int|false
    {
        $long = ip2long($ip);

        if ($long === false) {
            return false;
        }

        // Conversion unsigned 32-bit pour PHP
        return $long & 0xFFFFFFFF;
    }
}
