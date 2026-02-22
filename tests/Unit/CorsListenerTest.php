<?php

use Mitsuki\Http\Requests\Request;
use Mitsuki\Http\Responses\Response;
use Mitsuki\Listeners\CorsListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

test('adds CORS headers for allowed IP on main request', function () {
    $request = Request::create('/api/test', 'GET', [], [], [], [
        'REMOTE_ADDR' => '127.0.0.1',
    ]);

    $response = new Response();
    $event = new ResponseEvent(
        $this->kernel,
        $request,
        HttpKernelInterface::MAIN_REQUEST,
        $response
    );

    $this->listener->onKernelResponse($event);

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('*')
        ->and($response->headers->get('Access-Control-Allow-Methods'))->toBe('GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->and($response->headers->get('Access-Control-Allow-Headers'))->toContain('Authorization');
});

test('does not add CORS headers for sub-request', function () {
    $request = Request::create('/api/test');
    $response = new Response();
    $event = new ResponseEvent(
        $this->kernel,
        $request,
        HttpKernelInterface::SUB_REQUEST,
        $response
    );

    $this->listener->onKernelResponse($event);

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBeNull();
});

test('allows all requests when no allowed IPs configured', function () {
    $listener = new CorsListener([]);
    $request = Request::create('/api/test', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);
    $response = new Response();
    $event = new ResponseEvent(
        $this->kernel,
        $request,
        HttpKernelInterface::MAIN_REQUEST,
        $response
    );

    $listener->onKernelResponse($event);

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('*');
});

test('blocks CORS headers for non-allowed IP', function () {
    $listener = new CorsListener(['127.0.0.1']);
    $request = Request::create('/api/test', 'GET', [], [], [], ['REMOTE_ADDR' => '8.8.8.8']);
    $response = new Response();
    $event = new ResponseEvent(
        $this->kernel,
        $request,
        HttpKernelInterface::MAIN_REQUEST,
        $response
    );

    $listener->onKernelResponse($event);

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBeNull();
});

test('allows exact IP match', function () {
    $listener = new CorsListener(['192.168.1.10']);
    $request = Request::create('/api/test', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.1.10']);
    $response = new Response();
    $event = new ResponseEvent(
        $this->kernel,
        $request,
        HttpKernelInterface::MAIN_REQUEST,
        $response
    );

    $listener->onKernelResponse($event);

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('*');
});

test('allows IP in CIDR range 192.168.1.0/24', function () {
    $listener = new CorsListener(['192.168.1.0/24']);
    $request = Request::create('/api/test', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.1.55']);
    $response = new Response();
    $event = new ResponseEvent(
        $this->kernel,
        $request,
        HttpKernelInterface::MAIN_REQUEST,
        $response
    );

    $listener->onKernelResponse($event);

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('*');
});

test('rejects IP outside CIDR range', function () {
    $listener = new CorsListener(['192.168.1.0/24']);
    $request = Request::create('/api/test', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.2.55']);
    $response = new Response();
    $event = new ResponseEvent(
        $this->kernel,
        $request,
        HttpKernelInterface::MAIN_REQUEST,
        $response
    );

    $listener->onKernelResponse($event);

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBeNull();
});

test('includes credentials and max-age headers', function () {
    $request = Request::create('/api/test', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
    $response = new Response();
    $event = new ResponseEvent(
        $this->kernel,
        $request,
        HttpKernelInterface::MAIN_REQUEST,
        $response
    );

    $this->listener->onKernelResponse($event);

    expect($response->headers->get('Access-Control-Allow-Credentials'))->toBe('true')
        ->and($response->headers->get('Access-Control-Max-Age'))->toBe('86400');
});
