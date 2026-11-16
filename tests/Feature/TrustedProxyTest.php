<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Proxy dipercaya lewat config trustedproxy.proxies (TRUSTED_PROXIES), bawaannya '*'. */
class TrustedProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::get('/_tes-proxy', fn () => request()->getScheme().'|'.request()->ip());
    }

    private function viaProxy()
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.7'])
            ->get('/_tes-proxy');
    }

    public function test_default_trusts_any_proxy(): void
    {
        $this->assertSame('*', config('trustedproxy.proxies'));
        $this->viaProxy()->assertOk()->assertSeeText('https|203.0.113.7');
    }

    public function test_untrusted_proxy_headers_are_ignored(): void
    {
        config(['trustedproxy.proxies' => '192.168.0.1']);
        $this->viaProxy()->assertOk()->assertSeeText('http|10.1.2.3');
    }

    public function test_listed_proxy_is_trusted(): void
    {
        config(['trustedproxy.proxies' => '192.168.0.1, 10.1.2.0/24']);
        $this->viaProxy()->assertOk()->assertSeeText('https|203.0.113.7');
    }
}
