<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class BootstrapInstallerSourceGateTest extends TestCase
{
    public function test_recent_reviewed_sources_reach_signature_verification_and_never_accept_an_unsigned_bundle(): void
    {
        foreach (['1.2.3', '1.2.4'] as $version) {
            [$directory, $application, $installer] = $this->fixture($version);
            try {
                $before = $this->snapshot($application);
                $process = $this->runInstaller($installer, $application);
                $this->assertSame(1, $process->getExitCode(), $version);
                $this->assertStringContainsString('Official bootstrap signature verification failed.', $process->getErrorOutput(), $version);
                $this->assertStringNotContainsString('supports only', $process->getErrorOutput(), $version);
                $this->assertSame($before, $this->snapshot($application), 'Rejected bundle changed application files.');
                $this->assertDirectoryDoesNotExist($application.'/storage/app/updates/bootstrap');
            } finally {
                $this->remove($directory);
            }
        }
    }

    public function test_an_unreviewed_source_is_rejected_before_signature_or_application_mutation(): void
    {
        [$directory, $application, $installer] = $this->fixture('9.0.0');
        try {
            $before = $this->snapshot($application);
            $process = $this->runInstaller($installer, $application);
            $this->assertSame(1, $process->getExitCode());
            $this->assertStringContainsString('This bootstrap supports only', $process->getErrorOutput());
            $this->assertStringNotContainsString('Official bootstrap signature', $process->getErrorOutput());
            $this->assertSame($before, $this->snapshot($application));
        } finally {
            $this->remove($directory);
        }
    }

    private function fixture(string $version): array
    {
        $directory = sys_get_temp_dir().'/campaign-bootstrap-gate-'.bin2hex(random_bytes(8));
        $application = $directory.'/application';
        $bundle = $directory.'/bundle';
        mkdir($application.'/vendor', 0700, true);
        mkdir($bundle, 0700, true);
        file_put_contents($application.'/.env', "APP_ENV=testing\n");
        file_put_contents($application.'/vendor/autoload.php', "<?php\n");
        file_put_contents($application.'/release-manifest.json', json_encode(['version' => $version], JSON_THROW_ON_ERROR));
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $publicKey = openssl_pkey_get_details($key)['key'];
        // Only the public test key is written. The real CLI executes unchanged source/signature gates.
        $source = file_get_contents(__DIR__.'/../../tools/updater/bootstrap-install.php');
        $source = str_replace("'__CAMPAIGN_BOOTSTRAP_TRUSTED_KEY__'", var_export($publicKey, true), $source);
        file_put_contents($bundle.'/bootstrap-install.php', $source);
        file_put_contents($bundle.'/bootstrap-manifest.json', json_encode([
            'product' => 'Campaign Manager updater bootstrap', 'version' => '1.2.5',
            'checksums' => [], 'signature' => base64_encode(str_repeat("\0", 256)),
        ], JSON_THROW_ON_ERROR));
        return [$directory, $application, $bundle.'/bootstrap-install.php'];
    }

    private function runInstaller(string $installer, string $application): Process
    {
        $command = [PHP_BINARY];
        if ($ini = php_ini_loaded_file()) { array_push($command, '-c', $ini); }
        array_push($command, $installer, $application);
        $process = new Process($command);
        $process->setTimeout(15);
        $process->run();
        return $process;
    }

    private function snapshot(string $directory): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) { $files[substr($file->getPathname(), strlen($directory))] = hash_file('sha256', $file->getPathname()); }
        }
        ksort($files);
        return $files;
    }

    private function remove(string $directory): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $item) {
            if ($item->isDir()) { rmdir($item->getPathname()); } else { unlink($item->getPathname()); }
        }
        rmdir($directory);
    }
}
