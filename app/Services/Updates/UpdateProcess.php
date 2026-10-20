<?php

namespace App\Services\Updates;

use App\Services\Updates\UpdateException as RuntimeException;
use Symfony\Component\Process\Process;

class UpdateProcess
{
    public static function php(): string
    {
        $php = config('updates.php_cli') ?: (PHP_SAPI === 'cli' ? PHP_BINARY : PHP_BINDIR.'/php');
        if (! is_file($php) || ! is_executable($php) || ! function_exists('proc_open')) {
            throw new RuntimeException('The server needs an executable PHP CLI and proc_open for automatic updates.');
        }
        $p = new Process([$php, '-r', 'echo PHP_SAPI." ".PHP_VERSION_ID;']);
        $p->setTimeout(15);
        $p->run();
        if (! $p->isSuccessful() || ! preg_match('/^cli (\d+)$/D', trim($p->getOutput()), $m) || (int) $m[1] < 80200) {
            throw new RuntimeException('A PHP 8.2+ command-line runtime is required.');
        }

        return $php;
    }

    public static function artisan(array $args, int $timeout = 600): void
    {
        $arguments = [self::php(), base_path('artisan')];
        $directory = getenv('CAMPAIGN_UPDATE_JOB_DIR');
        if ($directory && is_file($directory.'/journal.json')) {
            // Keep a lease in the actual artisan child. If the worker crashes,
            // recovery waits until this command exits instead of racing it.
            $guard = '$cmUpdateCommandLease=fopen(getenv("CAMPAIGN_UPDATE_JOB_DIR")."/commands.lock","c"); if(!$cmUpdateCommandLease || !flock($cmUpdateCommandLease,LOCK_EX)){exit(1);} require '.var_export(base_path('artisan'), true).';';
            $arguments = [self::php(), '-r', $guard, '--'];
        }
        $environment = $directory ? ['CAMPAIGN_UPDATE_JOB_DIR' => $directory] : null;
        $p = new Process(array_merge($arguments, $args), base_path(), $environment, null, $timeout);
        $p->run();
        if (! $p->isSuccessful()) {
            file_put_contents(UpdatePaths::root().'/command-errors.log', '['.gmdate('c').'] '.($args[0] ?? 'unknown')."\n".$p->getErrorOutput().$p->getOutput()."\n", FILE_APPEND | LOCK_EX);
            chmod(UpdatePaths::root().'/command-errors.log', 0600);
            throw new RuntimeException('Upgrade command failed: '.($args[0] ?? 'unknown').'. Review the private server logs.');
        }
    }

    public static function launch(string $id): void
    {
        if (PHP_OS_FAMILY === 'Windows' || ! is_executable('/bin/sh') || ! is_executable('/usr/bin/nohup')) {
            throw new RuntimeException('Background updates require a supported Unix hosting environment.');
        }
        $job = UpdatePaths::job($id);
        $php = self::php();
        $process = new Process(['/bin/sh', '-c', 'nohup "$1" "$2" "$3" >"$4" 2>&1 </dev/null & echo $!', 'campaign-updater', $php, $job.'/supervise.php', $id, $job.'/supervisor.log'], base_path());
        $process->setTimeout(10);
        $process->mustRun();
        if (! preg_match('/^\d+$/D', trim($process->getOutput()))) {
            throw new RuntimeException('Could not start the updater worker.');
        }
        @chmod($job.'/supervisor.log', 0600);
    }
}
