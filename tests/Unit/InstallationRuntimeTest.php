<?php

namespace Tests\Unit;

use App\Support\InstallationRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class InstallationRuntimeTest extends TestCase
{
    public function test_preflight_covers_every_locked_production_extension(): void
    {
        $lock = json_decode(file_get_contents(__DIR__.'/../../composer.lock'), true);
        foreach ($lock['packages'] as $package) {
            foreach (array_keys($package['require'] ?? []) as $requirement) {
                if (str_starts_with($requirement, 'ext-')) {
                    $this->assertContains(strtolower(substr($requirement, 4)), InstallationRuntime::requiredExtensions(), $package['name'].' requires '.$requirement);
                }
            }
        }
    }

    public function test_missing_xml_modules_are_reported_before_installation(): void
    {
        $this->assertSame(['dom', 'simplexml', 'xmlreader', 'xmlwriter'], InstallationRuntime::missingExtensions(static fn ($extension) => !in_array($extension, ['dom', 'simplexml', 'xmlreader', 'xmlwriter'], true)));
    }

    public function test_cli_diagnostic_does_not_disclose_environment_secrets_or_write_setup_files(): void
    {
        $root = sys_get_temp_dir().'/campaign-runtime-'.bin2hex(random_bytes(8));
        mkdir($root.'/tools/install', 0700, true);
        mkdir($root.'/app/Support', 0700, true);
        copy(__DIR__.'/../../tools/install/diagnose.php', $root.'/tools/install/diagnose.php');
        copy(__DIR__.'/../../app/Support/InstallationRuntime.php', $root.'/app/Support/InstallationRuntime.php');
        $env = "APP_KEY=private-key-sentinel\nDB_PASSWORD=private-password-sentinel\n";
        file_put_contents($root.'/.env', $env);
        try {
            $process = new Process([PHP_BINARY, $root.'/tools/install/diagnose.php', '--json']);
            $process->run();
            $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(1, $process->getExitCode());
            $this->assertFalse($result['passed']);
            $this->assertStringNotContainsString('private-key-sentinel', $process->getOutput());
            $this->assertStringNotContainsString('private-password-sentinel', $process->getOutput());
            $this->assertSame($env, file_get_contents($root.'/.env'));
            $this->assertDirectoryDoesNotExist($root.'/storage');
        } finally {
            unlink($root.'/.env');
            unlink($root.'/tools/install/diagnose.php');
            unlink($root.'/app/Support/InstallationRuntime.php');
            rmdir($root.'/tools/install'); rmdir($root.'/tools');
            rmdir($root.'/app/Support'); rmdir($root.'/app'); rmdir($root);
        }
    }
}
