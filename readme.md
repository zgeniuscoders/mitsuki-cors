# Mitsuki CORS Listener

**Official CORS listener for the Mitsuki PHP framework** with configurable IP whitelisting support. Production-ready for secure REST APIs, microservices, and mobile backends.

## ✨ Features

- **Standard CORS headers** support (Origin, Methods, Headers, Credentials, Max-Age)
- **Configurable IP whitelisting** via `.env` (exact IPs + CIDR ranges: `192.168.1.0/24`)
- **Main request only** (`isMainRequest()` validation)
- **Symfony `kernel.response` event** listener
- **Production-ready CIDR validation** (`ip2long` + subnet mask)
- **Zero runtime dependencies** beyond core Mitsuki contracts

## 📦 Installation

```bash
composer require mitsuki/cors
```

**Production Requirements:**
- PHP `^8.1`
- `mitsuki/listener-contracts:^1.0`

**Development Dependencies:**
- `mitsuki/http:^1.0` (unit tests only)
- `pestphp/pest:^4.4` (testing)

## 📋 Usage Examples

### Development (Allow All)
```env
CORS_ALLOWED_IPS=""
```

### Production Security
```env
# Localhost + Docker + VPS range
CORS_ALLOWED_IPS="127.0.0.1,::1,192.168.1.0/24,10.96.0.0/12"
```

### Generated Response Headers
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS
Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization
Access-Control-Allow-Credentials: true
Access-Control-Max-Age: 86400
```

## 🧪 Testing

```bash
# Install dev dependencies (including mitsuki/http for tests)
composer install

# Run full test suite
./vendor/bin/pest

# Run specific tests
./vendor/bin/pest tests/Unit/CorsListenerTest.php
```

**Test Coverage:**
- Main vs sub-request handling
- Exact IP matching (`127.0.0.1`)
- CIDR range validation (`192.168.1.55` ∈ `192.168.1.0/24`)
- Fallback behavior (empty config = allow all)
- Full CORS headers verification

## 🏗️ Architecture

```
Mitsuki\Listeners\CorsListener implements ListenerResponseInterface
├── __construct(array $allowedIps = [])
├── onKernelResponse(ResponseEvent $event)
│   ├── if (!$event->isMainRequest()) return
│   ├── $clientIp = $request->getClientIp()
│   ├── if (!$this->isIpAllowed($clientIp)) return
│   └── $response->headers->set() // CORS headers
├── isIpAllowed(string $ip): bool
│   └── foreach($allowedIps) { CIDR/exact match }
└── ipInCidr(string $ip, string $cidr): bool
    └── ip2long() + subnet mask logic
```

## 🔧 Advanced Usage

### IPv6 Support
```env
CORS_ALLOWED_IPS="2001:db8::/32,::1,127.0.0.1"
```

### Custom Headers/Methods
```php
// Extend the listener
class CustomCorsListener extends CorsListener
{
    protected function setCorsHeaders(Response $response): void
    {
        parent::setCorsHeaders($response);
        $response->headers->set('Access-Control-Allow-Headers', 'X-API-Key,Authorization');
    }
}
```

## 🎯 Perfect For

- **REST APIs** → Flutter/React/Vue SPAs
- **Microservices** → Docker/Kubernetes networking
- **Secure deployments** → VPS/hosting providers
- **JWT/OAuth2** → API authentication flows

## 📁 Repository Structure

```
mitsuki/cors/
├── src/
│   └── CorsListener.php
├── tests/
│   └── Unit/
│       └── CorsListenerTest.php
├── composer.json
├── README.md
└── LICENSE
```

## 📄 License

MIT License © 2026 [ZGenius Matondo](mailto:zgeniuscoders@gmail.com)

```
Made with ❤️ for the Mitsuki PHP framework
```

***