<?php

/** Destructive only against an explicitly named local synthetic test database. */
use App\Models\Country;
use App\Models\Region;
use App\Models\State;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Enterprise\CampaignFinanceService;
use App\Services\Enterprise\EntitlementService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Process\Process;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, (string) $error.PHP_EOL);
    exit(1);
});
if (!app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql'
    || !preg_match('/_test$/D', DB::connection()->getDatabaseName())
    || !in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Finance concurrency verification requires a local MySQL/MariaDB database named *_test and APP_ENV=testing.');
}
$app->instance(EntitlementService::class, new class extends EntitlementService {
    public function __construct() {}
    public function active(string $feature, bool $requireOnline = false): bool { return $feature === 'campaign_finance'; }
});
$finance = app(CampaignFinanceService::class);

if (($argv[1] ?? '') === 'worker') {
    [$program, $mode, $barrier, $workerId, $actorId, $accountId, $requestId, $amount, $key] = $argv;
    file_put_contents($barrier.'/ready-'.$workerId, 'ready');
    $deadline = microtime(true) + 15;
    while (!is_file($barrier.'/go')) {
        if (microtime(true) > $deadline) { throw new RuntimeException('Timed out waiting for concurrent workers.'); }
        usleep(10000);
    }
    try {
        $id = $finance->record(User::findOrFail($actorId), (int)$accountId, 'expenditure', (int)$amount, 'Concurrent payment fixture', $key, (int)$requestId);
        echo json_encode(['ok' => true, 'id' => $id], JSON_THROW_ON_ERROR);
    } catch (ValidationException) {
        echo json_encode(['ok' => false, 'reason' => 'validation'], JSON_THROW_ON_ERROR);
    }
    exit;
}

if (Artisan::call('migrate:fresh', ['--force' => true]) !== 0) {
    throw new RuntimeException('Could not migrate the synthetic finance database.');
}
SystemSetting::create(['package' => 'presidential', 'system_name' => 'Concurrency test', 'system_country' => 'Nigeria']);
$country = Country::create(['name' => 'Nigeria', 'uuid' => (string)Str::uuid()]);
$region = Region::create(['name' => 'Synthetic region', 'country_id' => $country->id]);
$state = State::create(['name' => 'Synthetic state', 'region_id' => $region->id]);
$actors = [];
$permissions = ['finance.accounts.manage', 'finance.record', 'finance.budget.manage',
    'finance.disbursement.create', 'finance.disbursement.approve', 'finance.disbursement.execute'];
foreach (['requester', 'approver'] as $role) {
    $actor = User::create(['uuid' => (string)Str::uuid(), 'username' => 'finance-'.$role, 'email' => 'finance-'.$role.'@example.test',
        'firstname' => 'Finance', 'lastname' => ucfirst($role), 'password' => bcrypt('synthetic-password'),
        'access_level' => 'stateadmin', 'status' => 'active', 'state_id' => $state->id, 'region_id' => $region->id]);
    foreach ($permissions as $permission) {
        $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $actors[$role] = $actor;
}

DB::table('campaign_finance_governance')->insert([
    'previous_id' => 0, 'actor_id' => $actors['requester']->id, 'mode' => 'delegated',
    'grants' => json_encode(['level:stateadmin' => $permissions], JSON_THROW_ON_ERROR),
    'created_at' => now(),
]);

function concurrentFinanceWorkers(array $jobs): array
{
    $barrier = sys_get_temp_dir().'/campaign-finance-concurrency-'.Str::uuid();
    mkdir($barrier, 0700);
    $processes = [];
    try {
        foreach ($jobs as $index => $job) {
            $php = [PHP_BINARY];
            if ($ini = php_ini_loaded_file()) {
                array_push($php, '-c', $ini);
            }
            $process = new Process(array_merge($php, [__FILE__, 'worker', $barrier, (string) $index], array_map('strval', $job)), base_path());
            $process->setTimeout(30);
            $process->start();
            $processes[] = $process;
        }
        $deadline = microtime(true) + 15;
        while (count(glob($barrier.'/ready-*')) !== count($jobs)) {
            if (microtime(true) > $deadline) { throw new RuntimeException('Workers did not reach the concurrency barrier.'); }
            foreach ($processes as $process) {
                if (!$process->isRunning()) { throw new RuntimeException('Worker stopped before the barrier: '.$process->getErrorOutput().$process->getOutput()); }
            }
            usleep(10000);
        }
        touch($barrier.'/go');
        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            if (!$process->isSuccessful()) { throw new RuntimeException('Concurrent worker failed: '.$process->getErrorOutput().$process->getOutput()); }
            $results[] = json_decode($process->getOutput(), true, 32, JSON_THROW_ON_ERROR);
        }
        return $results;
    } finally {
        foreach ($processes as $process) { if ($process->isRunning()) { $process->stop(); } }
        foreach (glob($barrier.'/*') as $file) { unlink($file); }
        rmdir($barrier);
    }
}
function financeCheck(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

$account = $finance->createAccount($actors['requester'], 'Concurrent budget account', $actors['requester']->id);
$finance->record($actors['requester'], $account->id, 'income', 10000, 'Opening fixture funds', (string)Str::uuid());
$budget = $finance->createBudget($actors['requester'], $account->id, 'Concurrent budget', 20000);
$jobs = [];
foreach ([1, 2] as $n) {
    $request = $finance->request($actors['requester'], $account->id, $budget, 8000, 'Concurrent request '.$n);
    $finance->decide($actors['approver'], $request, true);
    $jobs[] = [$actors['requester']->id, $account->id, $request, 8000, (string)Str::uuid()];
}
$results = concurrentFinanceWorkers($jobs);
financeCheck(count(array_filter($results, fn ($row) => $row['ok'])) === 1, 'Exactly one competing overspend must succeed.');
financeCheck($account->fresh()->balance_minor === 2000, 'Concurrent overspend changed the balance incorrectly.');
financeCheck((int)DB::table('campaign_finance_budgets')->where('id', $budget)->value('utilized_minor') === 8000, 'Concurrent budget utilization is inconsistent.');
financeCheck(DB::table('campaign_finance_requests')->where('account_id', $account->id)->where('status', 'recorded')->count() === 1, 'Competing requests were both recorded.');

$account = $finance->createAccount($actors['requester'], 'Concurrent allocation account', $actors['requester']->id);
$finance->record($actors['requester'], $account->id, 'income', 20000, 'Opening fixture funds', (string)Str::uuid());
$budget = $finance->createBudget($actors['requester'], $account->id, 'Constrained budget', 10000);
$jobs = [];
foreach ([1, 2] as $n) {
    $request = $finance->request($actors['requester'], $account->id, $budget, 8000, 'Allocation request '.$n);
    $finance->decide($actors['approver'], $request, true);
    $jobs[] = [$actors['requester']->id, $account->id, $request, 8000, (string)Str::uuid()];
}
$results = concurrentFinanceWorkers($jobs);
financeCheck(count(array_filter($results, fn ($row) => $row['ok'])) === 1, 'Exactly one competing budget expenditure must succeed.');
financeCheck($account->fresh()->balance_minor === 12000, 'Concurrent budget refusal changed the account balance incorrectly.');
financeCheck((int)DB::table('campaign_finance_budgets')->where('id', $budget)->value('utilized_minor') === 8000, 'Concurrent budget allocation was exceeded.');

$account = $finance->createAccount($actors['requester'], 'Duplicate callback account', $actors['requester']->id);
$finance->record($actors['requester'], $account->id, 'income', 10000, 'Opening fixture funds', (string)Str::uuid());
$request = $finance->request($actors['requester'], $account->id, null, 1000, 'Duplicate submission');
$finance->decide($actors['approver'], $request, true);
$job = [$actors['requester']->id, $account->id, $request, 1000, (string)Str::uuid()];
$results = concurrentFinanceWorkers([$job, $job]);
financeCheck($results[0]['ok'] && $results[1]['ok'] && $results[0]['id'] === $results[1]['id'], 'Concurrent identical submissions did not return the same ledger entry.');
financeCheck($account->fresh()->balance_minor === 9000, 'Duplicate submission debited the account more than once.');
financeCheck(DB::table('campaign_finance_entries')->where('account_id', $account->id)->where('kind', 'expenditure')->count() === 1, 'Duplicate expenditure entries were persisted.');
echo json_encode(['passed' => true, 'actual_concurrent_processes' => true, 'overspend_serialized' => true, 'budget_consistent' => true, 'budget_overspend_serialized' => true, 'duplicate_submission_idempotent' => true], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
