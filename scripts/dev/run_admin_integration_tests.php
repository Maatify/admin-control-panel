<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$basePath = dirname(__DIR__, 2);
require $basePath . '/vendor/autoload.php';

if (!is_file($basePath . '/.env')) {
    throw new RuntimeException('Admin integration clone requires a real .env file at the project root.');
}

Dotenv::createImmutable($basePath, '.env')->safeLoad();

$options = [
    'mode' => 'clone',
    'refresh' => false,
    'help' => false,
    'target' => null,
];
$testArguments = [];
$afterSeparator = false;

foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--') {
        $afterSeparator = true;
        continue;
    }

    if ($afterSeparator) {
        $testArguments[] = $argument;
        continue;
    }

    if ($argument === '--help' || $argument === '-h') {
        $options['help'] = true;
        continue;
    }

    if ($argument === '--refresh') {
        $options['refresh'] = true;
        continue;
    }

    if (str_starts_with($argument, '--mode=')) {
        $options['mode'] = substr($argument, 7);
        continue;
    }

    if (str_starts_with($argument, '--target=')) {
        $options['target'] = substr($argument, 9);
        continue;
    }

    throw new RuntimeException("Unknown option: {$argument}");
}

if ($options['help']) {
    echo <<<'HELP'
Usage:
  php scripts/dev/run_admin_integration_tests.php [--mode=clone] [--refresh] [--target=DB_test] -- [PHPUnit arguments]

Examples:
  php scripts/dev/run_admin_integration_tests.php --mode=clone --refresh
  php scripts/dev/run_admin_integration_tests.php --mode=clone --refresh -- --filter AdminCreationTest

The clone mode reads the Admin source connection from the root .env, creates
an isolated database named exactly ADMIN_DB_NAME with a trailing _test, and
clones the source database into it. The runner never writes to the source
database and refuses any target that is not the derived _test database.
The cloned database is the only database used by PHPUnit.
HELP;
    exit(0);
}

if ($options['mode'] !== 'clone') {
    throw new RuntimeException('The Admin integration runner currently supports only --mode=clone.');
}

$appEnv = envValue('ADMIN_APP_ENV');
$sourceHost = envValue('ADMIN_DB_HOST');
$sourcePort = envValue('ADMIN_DB_PORT') !== '' ? envValue('ADMIN_DB_PORT') : '3306';
$sourceName = envValue('ADMIN_DB_NAME');
$sourceUser = envValue('ADMIN_DB_USER');
$sourcePass = envValue('ADMIN_DB_PASS');
$targetName = is_string($options['target']) && $options['target'] !== ''
    ? $options['target']
    : $sourceName . '_test';

if (!in_array($appEnv, ['local', 'dev', 'development'], true)) {
    throw new RuntimeException('The Admin integration runner requires ADMIN_APP_ENV=local, dev, or development.');
}

if ($sourceHost === '' || $sourceName === '' || $sourceUser === '' || $sourcePass === '') {
    throw new RuntimeException('ADMIN_DB_HOST, ADMIN_DB_NAME, ADMIN_DB_USER and ADMIN_DB_PASS are required in .env.');
}

if ($sourceName === $targetName || $targetName !== $sourceName . '_test') {
    throw new RuntimeException('The target must be exactly ADMIN_DB_NAME with _test appended.');
}

$rootPdo = connect($sourceHost, $sourcePort, $sourceUser, $sourcePass);
if (!databaseExists($rootPdo, $sourceName)) {
    throw new RuntimeException("Source database does not exist: {$sourceName}");
}

if (databaseExists($rootPdo, $targetName)) {
    if (!$options['refresh']) {
        throw new RuntimeException("Target {$targetName} already exists; rerun with --refresh.");
    }

    $rootPdo->exec('DROP DATABASE ' . quoteIdentifier($targetName));
}

$rootPdo->exec('CREATE DATABASE ' . quoteIdentifier($targetName) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

try {
    cloneDatabase($sourceHost, $sourcePort, $sourceUser, $sourcePass, $sourceName, $targetName);
} catch (Throwable $exception) {
    $rootPdo->exec('DROP DATABASE IF EXISTS ' . quoteIdentifier($targetName));
    throw $exception;
}

if ($testArguments === []) {
    $testArguments = ['tests/admin', '--filter', 'Integration'];
}

fwrite(STDERR, "Running Admin integration tests on {$targetName} via {$sourceHost}:{$sourcePort}.\n");

$environment = processEnvironment([
    'ADMIN_APP_ENV' => 'testing',
    'ADMIN_DB_HOST' => $sourceHost,
    'ADMIN_DB_PORT' => $sourcePort,
    'ADMIN_DB_NAME' => $targetName,
    'ADMIN_DB_USER' => $sourceUser,
    'ADMIN_DB_PASS' => $sourcePass,
]);

exit(runPhpUnit($basePath, $environment, $testArguments));

function envValue(string $key): string
{
    $fromEnvironment = getenv($key);
    if ($fromEnvironment !== false && $fromEnvironment !== '') {
        return $fromEnvironment;
    }

    $value = $_ENV[$key] ?? null;
    return is_string($value) ? $value : '';
}

function connect(string $host, string $port, string $user, string $pass, ?string $database = null): PDO
{
    $dsn = 'mysql:host=' . $host . ';port=' . $port . ';charset=utf8mb4';
    if ($database !== null) {
        $dsn .= ';dbname=' . $database;
    }

    return new PDO(
        $dsn,
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
}

function databaseExists(PDO $pdo, string $database): bool
{
    $statement = $pdo->prepare(
        'SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
    );
    $statement->execute([$database]);

    return $statement->fetchColumn() !== false;
}

function quoteIdentifier(string $identifier): string
{
    if (preg_match('/^[A-Za-z0-9_]+$/', $identifier) !== 1) {
        throw new RuntimeException('Unsafe database identifier.');
    }

    return '`' . $identifier . '`';
}

function cloneDatabase(
    string $host,
    string $port,
    string $user,
    string $pass,
    string $source,
    string $target,
): void {
    // These flags work with both MySQL and MariaDB. Scheduled events are
    // deliberately excluded so a test clone cannot start source-side jobs.
    $dump = [
        'mysqldump',
        '--single-transaction',
        '--routines',
        '--triggers',
        '--hex-blob',
        '--no-tablespaces',
        '--host=' . $host,
        '--port=' . $port,
        '--user=' . $user,
        $source,
    ];
    $restore = [
        'mysql',
        '--host=' . $host,
        '--port=' . $port,
        '--user=' . $user,
        $target,
    ];
    $pipeline = implode(' ', array_map('escapeshellarg', $dump))
        . ' | '
        . implode(' ', array_map('escapeshellarg', $restore));

    $process = proc_open(
        'bash -o pipefail -c ' . escapeshellarg($pipeline),
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        null,
        processEnvironment(['MYSQL_PWD' => $pass]),
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start mysqldump/mysql clone pipeline.');
    }

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        throw new RuntimeException('Database clone failed: ' . trim((string) $stderr));
    }
}

/** @param array<string, string> $overrides */
function processEnvironment(array $overrides = []): array
{
    $environment = [];
    foreach ($_ENV as $key => $value) {
        if (is_scalar($value)) {
            $environment[$key] = (string) $value;
        }
    }

    $path = getenv('PATH');
    if ($path !== false) {
        $environment['PATH'] = $path;
    }

    foreach ($overrides as $key => $value) {
        $environment[$key] = $value;
    }

    return $environment;
}

/** @param list<string> $testArguments */
function runPhpUnit(string $basePath, array $environment, array $testArguments): int
{
    $arguments = [
        PHP_BINARY,
        $basePath . '/vendor/bin/phpunit',
        '--configuration',
        $basePath . '/phpunit.admin.xml',
        '--colors=never',
        ...$testArguments,
    ];
    $command = implode(' ', array_map('escapeshellarg', $arguments));
    $process = proc_open(
        $command,
        [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $basePath,
        $environment,
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start PHPUnit.');
    }

    while (!feof($pipes[1]) || !feof($pipes[2])) {
        $read = [];
        if (!feof($pipes[1])) {
            $read[] = $pipes[1];
        }
        if (!feof($pipes[2])) {
            $read[] = $pipes[2];
        }
        if ($read === []) {
            break;
        }

        $write = null;
        $except = null;
        if (stream_select($read, $write, $except, 1) === false) {
            break;
        }

        foreach ($read as $stream) {
            $chunk = fread($stream, 8192);
            if ($chunk !== false && $chunk !== '') {
                echo $chunk;
            }
        }
    }

    fclose($pipes[1]);
    fclose($pipes[2]);

    return proc_close($process);
}
