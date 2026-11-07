<?php

namespace Hybridauth\HttpClient;

use Hybridauth\Exception\InvalidArgumentException;

/** Resolve untrusted OpenID URLs once, so transports can pin the checked address. */
class Url
{
    public static function resolve($url, $trustedEndpoints = [])
    {
        if (!is_string($url) || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            throw new InvalidArgumentException('Invalid OpenID URL.');
        }
        $parts = parse_url($url);
        if (!$parts || empty($parts['host']) || empty($parts['scheme'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])
        ) {
            throw new InvalidArgumentException('OpenID URLs must use HTTP(S) without credentials.');
        }
        $host = strtolower(trim($parts['host'], '[]'));
        if (!filter_var($host, FILTER_VALIDATE_IP) && !preg_match('/^[a-z0-9.-]+$/D', $host)) {
            throw new InvalidArgumentException('Invalid OpenID hostname.');
        }
        $ranges = static::trustedRanges($parts, $trustedEndpoints);
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : static::resolveHost($host);
        if (!$addresses) {
            throw new InvalidArgumentException('Unable to resolve OpenID hostname.');
        }
        foreach ($addresses as $address) {
            $allowed = static::isPublicAddress($address);
            foreach ($ranges as $range) {
                $allowed = $allowed || static::inRange($address, $range);
            }
            if (!$allowed) {
                throw new InvalidArgumentException('OpenID requests to non-public addresses are forbidden.');
            }
        }
        return [
            'host' => $host,
            'address' => reset($addresses),
            'port' => isset($parts['port']) ? $parts['port'] : (strtolower($parts['scheme']) === 'https' ? 443 : 80),
            'parts' => $parts,
        ];
    }

    /** IPv4 answers come first, so dual-stack hosts keep pinning to IPv4. */
    protected static function resolveHost($host)
    {
        return array_values(array_unique(array_merge(static::lookupIpv4($host), static::lookupIpv6($host))));
    }

    protected static function lookupIpv4($host)
    {
        $addresses = gethostbynamel($host);
        return $addresses ?: [];
    }

    protected static function lookupIpv6($host)
    {
        if (!function_exists('dns_get_record') || !defined('DNS_AAAA')) {
            return [];
        }
        $records = @dns_get_record($host, DNS_AAAA);
        $addresses = [];
        foreach ($records ?: [] as $record) {
            if (isset($record['ipv6']) && filter_var($record['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $addresses[] = strtolower($record['ipv6']);
            }
        }
        return $addresses;
    }

    private static function trustedRanges($parts, $rules)
    {
        if (!is_array($rules)) {
            throw new InvalidArgumentException('OpenID trusted endpoints must be an array.');
        }
        $ranges = [];
        foreach ($rules as $rule) {
            if (!is_array($rule) || !isset($rule['origin'], $rule['addresses'])
                || !is_string($rule['origin']) || !is_array($rule['addresses']) || !$rule['addresses']
                || preg_match('/[\x00-\x20\x7f\\\\]/', $rule['origin'])
            ) {
                throw new InvalidArgumentException('Each trusted OpenID origin requires explicit addresses or CIDRs.');
            }
            $origin = parse_url($rule['origin']);
            if (!$origin || empty($origin['scheme']) || empty($origin['host'])
                || !in_array(strtolower($origin['scheme']), ['http', 'https'], true)
                || isset($origin['user']) || isset($origin['pass'])
                || isset($origin['query']) || isset($origin['fragment'])
                || (isset($origin['path']) && $origin['path'] !== '' && $origin['path'] !== '/')
            ) {
                throw new InvalidArgumentException(
                    'Trusted OpenID origins must specify an exact HTTP(S) scheme, host and optional port.'
                );
            }
            $originHost = trim($origin['host'], '[]');
            if (!filter_var($originHost, FILTER_VALIDATE_IP) && !preg_match('/^[a-z0-9.-]+$/iD', $originHost)) {
                throw new InvalidArgumentException('Trusted OpenID origins cannot contain hostname patterns.');
            }
            $sameOrigin = strtolower($parts['scheme']) === strtolower($origin['scheme'])
                && strtolower($parts['host']) === strtolower($origin['host'])
                && static::port($parts) === static::port($origin);
            foreach ($rule['addresses'] as $range) {
                if (!is_string($range)) {
                    throw new InvalidArgumentException('Invalid trusted OpenID address range.');
                }
                $cidr = explode('/', $range);
                $address = filter_var($cidr[0], FILTER_VALIDATE_IP);
                $bits = strpos($cidr[0], ':') === false ? 32 : 128;
                $invalidBits = isset($cidr[1]) && (!ctype_digit($cidr[1]) || $cidr[1] > $bits);
                if (!$address || count($cidr) > 2 || $invalidBits) {
                    throw new InvalidArgumentException('Invalid trusted OpenID address range.');
                }
                if ($sameOrigin) {
                    $ranges[] = $range . (count($cidr) === 1 ? '/' . $bits : '');
                }
            }
        }
        return $ranges;
    }

    private static function port($parts)
    {
        return isset($parts['port']) ? $parts['port'] : (strtolower($parts['scheme']) === 'https' ? 443 : 80);
    }

    public static function redirect($base, $location)
    {
        if (!is_string($location) || preg_match('/[\x00-\x20\x7f\\\\]/', $location)) {
            throw new InvalidArgumentException('Invalid OpenID redirect.');
        }
        $next = parse_url($location);
        $parts = parse_url($base);
        if ($next === false || isset($next['user']) || isset($next['pass'])) {
            throw new InvalidArgumentException('Invalid OpenID redirect.');
        }
        if (isset($next['scheme'])) {
            return $location;
        }
        if (substr($location, 0, 2) === '//') {
            return $parts['scheme'] . ':' . $location;
        }
        $path = isset($parts['path']) ? $parts['path'] : '/';
        if (isset($next['path']) && $next['path'] !== '') {
            $path = $next['path'][0] === '/'
                ? $next['path']
                : substr($path, 0, strrpos($path, '/') + 1) . $next['path'];
            unset($parts['query']);
        }
        $trailingSlash = preg_match('#/(?:\.|\.\.)?$#', $path);
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                array_pop($segments);
            } elseif ($segment !== '.') {
                $segments[] = $segment;
            }
        }
        $path = '/' . ltrim(implode('/', $segments), '/');
        if ($trailingSlash && substr($path, -1) !== '/') {
            $path .= '/';
        }
        $query = isset($next['query']) ? $next['query'] : (isset($parts['query']) ? $parts['query'] : null);
        return $parts['scheme'] . '://' . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '') . ($path === '' ? '/' : $path)
            . ($query === null ? '' : '?' . $query);
    }

    public static function isPublicAddress($address)
    {
        if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        // Include shared, documentation, benchmarking, multicast and transition ranges.
        $blocked = strpos($address, ':') === false
            ? ['0.0.0.0/8', '100.64.0.0/10', '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24',
                '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4']
            : ['2001::/23', '2001:db8::/32', '2002::/16', '3fff::/20'];
        if (strpos($address, ':') !== false && !static::inRange($address, '2000::/3')) {
            return false;
        }
        foreach ($blocked as $range) {
            if (static::inRange($address, $range)) {
                return false;
            }
        }
        return true;
    }

    private static function inRange($address, $range)
    {
        list($network, $bits) = explode('/', $range);
        $address = inet_pton($address);
        $network = inet_pton($network);
        if ($address === false || $network === false || strlen($address) !== strlen($network)) {
            return false;
        }
        $bytes = (int) ($bits / 8);
        $remaining = $bits % 8;
        return substr($address, 0, $bytes) === substr($network, 0, $bytes)
            && (!$remaining || ((ord($address[$bytes]) ^ ord($network[$bytes])) & (255 << (8 - $remaining))) === 0);
    }
}
