<?php

namespace Mitsuki\Listeners;

use Mitsuki\Contracts\ListenerResponseInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * CORS Listener to add Cross-Origin Resource Sharing (CORS) headers
 * to HTTP responses in the Mitsuki application.
 *
 * This class implements the ListenerResponseInterface and listens to the
 * Symfony kernel.response event. It adds standard CORS headers to allow
 * cross-origin requests from external domains, such as Flutter mobile apps
 * or Next.js frontends. IP addresses are configurable via environment variables.
 *
 * @author Zgenius Matondo <zgeniuscoders@gmail.com>
 * @see https://symfony.com/doc/current/reference/events.html#kernel-response
 */
class CorsListener implements ListenerResponseInterface
{
    /**
     * @var array<string> List of allowed IPs or CIDR ranges (ex: ['127.0.0.1', '192.168.1.0/24'])
     */
    private array $allowedIps;

    /**
     * Constructor.
     *
     * @param array<string> $allowedIps Allowed IPs from .env configuration
     */
    public function __construct(array $allowedIps = [])
    {
        $this->allowedIps = $allowedIps;
    }

    /**
     * Handles the kernel.response event to add CORS headers.
     *
     * - Checks if it's the main request and if the client IP is allowed
     * - Adds CORS headers only for authorized IPs
     * - Supports standard HTTP methods and common headers for API usage
     *
     * @param ResponseEvent $event The kernel.response event containing the response
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $clientIp = $request->getClientIp();

        if (!$this->isIpAllowed($clientIp)) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');
    }

    /**
     * Checks if an IP address is allowed (supports CIDR notation via filter_var).
     *
     * @param string $ip IP address to validate
     * @return bool True if IP is allowed, false otherwise
     */
    private function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowedIps)) {
            return true; // Allow all if not configured
        }

        foreach ($this->allowedIps as $allowed) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && strpos($allowed, '/') !== false) {
                // CIDR range check (use ip-lib library for production)
                if ($this->ipInCidr($ip, $allowed)) {
                    return true;
                }
            } elseif ($ip === $allowed) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if an IP belongs to a CIDR range (basic implementation).
     *
     * Converts IP addresses to long format and applies subnet mask for comparison.
     *
     * @param string $ip IP address to check
     * @param string $cidr CIDR notation (ex: '192.168.1.0/24')
     * @return bool True if IP is within the CIDR range
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$network, $mask] = explode('/', $cidr);
        $ipLong = ip2long($ip);
        $networkLong = ip2long($network);
        $maskLong = ~((1 << (32 - $mask)) - 1);
        return ($ipLong & $maskLong) === ($networkLong & $maskLong);
    }
}
