<?php

declare(strict_types=1);

namespace App\Services;

/**
 * GraphqlEndpointValidator — validates the (admin-supplied) GitHub GraphQL endpoint
 * before the server ever calls it, as a guard against SSRF.
 *
 * Rules: https only; no credentials in the URL; port 443 (or none); host must not be
 * localhost / an internal name / a literal private, loopback or link-local address;
 * if the host resolves, none of its addresses may be private/reserved.
 */
final class GraphqlEndpointValidator
{
    public const DEFAULT_ENDPOINT = 'https://api.github.com/graphql';

    /**
     * @return string|null error message, or null when the endpoint is acceptable
     */
    public function validate(string $url): ?string
    {
        $url = trim($url);

        if ($url === '' || strlen($url) > 255) {
            return 'Endpoint must be a URL of at most 255 characters.';
        }

        $parts = parse_url($url);

        if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return 'Endpoint must be an https URL.';
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return 'Endpoint must not contain credentials.';
        }

        if (isset($parts['port']) && (int) $parts['port'] !== 443) {
            return 'Endpoint must use the standard https port.';
        }

        $host = strtolower(trim($parts['host'], '[]'));

        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal') || !str_contains($host, '.') && !filter_var($host, FILTER_VALIDATE_IP)) {
            return 'Endpoint host is not allowed.';
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->isPublicIp($host) ? null : 'Endpoint host is not allowed.';
        }

        // Best effort: when the name resolves, every address must be public.
        foreach ($this->resolve($host) as $ip) {
            if (!$this->isPublicIp($ip)) {
                return 'Endpoint host resolves to a non-public address.';
            }
        }

        return null;
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }

    /**
     * @return string[] resolved IPv4/IPv6 addresses (empty when the name does not resolve)
     */
    protected function resolve(string $host): array
    {
        $ips = [];

        foreach (@dns_get_record($host, DNS_A | DNS_AAAA) ?: [] as $record) {
            $ips[] = $record['ip'] ?? $record['ipv6'] ?? null;
        }

        return array_values(array_filter($ips));
    }
}
