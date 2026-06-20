<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\File;

class RequirementCheckService
{
    public function __construct(private SecurePathInspector $pathInspector)
    {
    }

    public function checks(): array
    {
        $checks = [
            $this->check('PHP '.PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>='), true, 'PHP 8.2 or newer is required.'),
        ];

        foreach (['pdo', 'pdo_mysql', 'openssl', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'curl', 'fileinfo', 'zip'] as $extension) {
            $checks[] = $this->check("PHP extension: {$extension}", extension_loaded($extension), true, "{$extension} extension is required.");
        }

        $checks[] = $this->check('PHP extension: gd or imagick', extension_loaded('gd') || extension_loaded('imagick'), true, 'Either gd or imagick is required.');

        foreach ([storage_path(), storage_path('app'), storage_path('framework'), storage_path('logs'), base_path('bootstrap/cache')] as $path) {
            $checks[] = $this->check("Writable: {$path}", File::isWritable($path), true, 'Folder must be writable.');
        }

        $envPath = base_path('.env');
        $checks[] = $this->check('.env writable or creatable', (File::exists($envPath) && is_writable($envPath)) || is_writable(base_path()), true, '.env must be writable or creatable.');
        $checks[] = $this->check('storage/app/public exists', File::exists(storage_path('app/public')) || File::makeDirectory(storage_path('app/public'), 0755, true, true), false, 'Uploads can be configured later if needed.');
        $checks[] = $this->check('installed.lock can be written later', is_writable(storage_path('app')), true, 'storage/app must be writable.');

        foreach ($this->pathInspector->inspect() as $warning) {
            $checks[] = ['label' => 'Hosting path inspection', 'status' => 'warning', 'message' => $warning, 'critical' => false];
        }

        return $checks;
    }

    public function hasCriticalFailures(array $checks): bool
    {
        return collect($checks)->contains(fn ($check) => $check['critical'] && $check['status'] === 'fail');
    }

    private function check(string $label, bool $passes, bool $critical, string $message): array
    {
        return [
            'label' => $label,
            'status' => $passes ? 'pass' : ($critical ? 'fail' : 'warning'),
            'message' => $passes ? 'OK' : $message,
            'critical' => $critical,
        ];
    }
}
