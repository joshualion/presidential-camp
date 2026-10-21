<?php

use App\Services\Updates\BootstrapHttpRequest;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->originalServer = $_SERVER;
    $this->originalCookies = $_COOKIE;
    $this->originalPost = $_POST;
});

afterEach(function () {
    $_SERVER = $this->originalServer;
    $_COOKIE = $this->originalCookies;
    $_POST = $this->originalPost;
});

test('bootstrap URLs use the application mount rather than the secondary script', function (string $mount) {
    $_SERVER = [
        'REQUEST_URI' => $mount.'/campaign-update-bootstrap.php?registration=1',
        'SCRIPT_NAME' => $mount.'/public/campaign-update-bootstrap.php',
        'PHP_SELF' => $mount.'/campaign-update-bootstrap.php',
        'SCRIPT_FILENAME' => __DIR__.'/../../public/campaign-update-bootstrap.php',
        'HTTP_HOST' => 'customer.example', 'HTTPS' => 'on', 'SERVER_PORT' => 443,
        'REQUEST_METHOD' => 'POST', 'PATH_INFO' => '/incorrect',
    ];
    $_COOKIE = ['customer_session' => 'existing-session'];
    $_POST = ['_token' => 'existing-csrf', 'action' => 'prepare'];

    $request = BootstrapHttpRequest::capture(__DIR__.'/../../public/index.php');
    $urls = new UrlGenerator(new RouteCollection, $request);

    expect($request->getBaseUrl())->toBe($mount)
        ->and($request->getPathInfo())->toBe('/campaign-update-bootstrap.php')
        ->and($request->getRequestUri())->toBe($mount.'/campaign-update-bootstrap.php?registration=1')
        ->and($request->method())->toBe('POST')
        ->and($request->input('_token'))->toBe('existing-csrf')
        ->and($request->cookie('customer_session'))->toBe('existing-session')
        ->and($urls->to('/login'))->toBe('https://customer.example'.$mount.'/login')
        ->and($urls->to('/superadmin/dashboard'))->toBe('https://customer.example'.$mount.'/superadmin/dashboard')
        ->and($urls->asset('build/assets/app.js'))->toBe('https://customer.example'.$mount.'/build/assets/app.js');
})->with(['root' => '', 'subdirectory' => '/campaign']);

test('bootstrap binds the captured request before real Laravel kernel initialization', function () {
    $source = <<<'PHP'
require 'vendor/autoload.php';
$_SERVER = ['REQUEST_URI'=>'/campaign-update-bootstrap.php', 'SCRIPT_NAME'=>'/public/campaign-update-bootstrap.php', 'HTTP_HOST'=>'customer.example', 'HTTPS'=>'on', 'SERVER_PORT'=>443, 'REQUEST_METHOD'=>'GET'];
$request = App\Services\Updates\BootstrapHttpRequest::capture(getcwd().'/public/index.php');
$app = require 'bootstrap/app.php';
$app->instance('request', $request);
Illuminate\Support\Facades\Facade::clearResolvedInstance('request');
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
if ($app->make('request') !== $request) { throw new RuntimeException('Request replaced'); }
echo $app->make('url')->to('/login');
PHP;
    $process = new Process([PHP_BINARY, '-r', $source], dirname(__DIR__, 2));
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput())
        ->and(trim($process->getOutput()))->toBe('https://customer.example/login');
});

test('bootstrap rejects an unrelated entry address', function () {
    $_SERVER['REQUEST_URI'] = '/unrelated.php';
    BootstrapHttpRequest::capture(__DIR__.'/../../public/index.php');
})->throws(RuntimeException::class, 'Invalid updater entry address.');

test('bootstrap rejects a missing application front controller', function () {
    BootstrapHttpRequest::capture(__DIR__.'/../../public/missing-index.php');
})->throws(RuntimeException::class, 'The existing application front controller is unavailable.');
