<?php
declare(strict_types=1);

/**
 * Runner de testes sem dependências externas.
 * Uso:
 *   php tests/run.php
 *   php tests/run.php Sigtap
 *   php tests/run.php Core
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once __DIR__ . '/helpers.php';

$filter = $argv[1] ?? null;

$allTestFiles = glob(__DIR__ . '/*Test.php') ?: [];
sort($allTestFiles, SORT_NATURAL);

if (empty($allTestFiles)) {
    echo "Nenhum arquivo de teste (*Test.php) encontrado em tests/.\n";
    exit(0);
}

$hasColors = (DIRECTORY_SEPARATOR === '/' || (function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(STDOUT)))
    || getenv('TERM') !== false;

$colorGreen = $hasColors ? "\033[32m" : '';
$colorRed   = $hasColors ? "\033[31m" : '';
$colorBold  = $hasColors ? "\033[1m" : '';
$colorDim   = $hasColors ? "\033[2m" : '';
$colorReset = $hasColors ? "\033[0m" : '';

$totalTests = 0;
$passedCount = 0;
$failedCount = 0;
$failures = [];
$startTime = microtime(true);

echo $colorBold . "SUS-systems — Suíte de Testes" . $colorReset . "\n";
if ($filter !== null) {
    echo "Filtro ativo: " . $colorDim . $filter . $colorReset . "\n";
}
echo str_repeat('-', 50) . "\n";

foreach ($allTestFiles as $file) {
    $baseName = basename($file);
    $fileMatches = $filter === null || (stripos($baseName, $filter) !== false);

    /** @var mixed $tests */
    $tests = require $file;
    if (!is_array($tests)) {
        continue;
    }

    // Filtra testes se o nome do arquivo não casou diretamente com o filtro
    $selectedTests = [];
    foreach ($tests as $name => $fn) {
        if (!is_callable($fn)) {
            continue;
        }
        if ($fileMatches || (stripos((string) $name, (string) $filter) !== false)) {
            $selectedTests[$name] = $fn;
        }
    }

    if (empty($selectedTests)) {
        continue;
    }

    echo "\n" . $colorBold . $baseName . $colorReset . "\n";

    foreach ($selectedTests as $testName => $testFn) {
        $totalTests++;
        $t0 = microtime(true);
        try {
            $testFn();
            $durationMs = round((microtime(true) - $t0) * 1000, 1);
            $passedCount++;
            echo "  {$colorGreen}✓{$colorReset} {$testName} {$colorDim}({$durationMs}ms){$colorReset}\n";
        } catch (\Throwable $e) {
            $failedCount++;
            echo "  {$colorRed}✗{$colorReset} {$testName}\n";
            $failures[] = [
                'file'      => $baseName,
                'name'      => $testName,
                'exception' => $e,
            ];
        }
    }
}

$totalDuration = round(microtime(true) - $startTime, 3);

echo "\n" . str_repeat('=', 50) . "\n";
if ($totalTests === 0) {
    echo "{$colorRed}Nenhum teste encontrado" . ($filter !== null ? " correspondente ao filtro '{$filter}'" : "") . ".{$colorReset}\n";
    exit(1);
}

if ($failedCount > 0) {
    echo "\n" . $colorBold . $colorRed . "FALHAS (" . $failedCount . "):" . $colorReset . "\n\n";
    foreach ($failures as $idx => $fail) {
        $num = $idx + 1;
        /** @var \Throwable $e */
        $e = $fail['exception'];
        echo "{$num}) {$fail['file']} > {$fail['name']}\n";
        echo "   {$colorRed}" . get_class($e) . ": " . $e->getMessage() . "{$colorReset}\n";
        echo "   em " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "   Trace:\n";
        $traceLines = explode("\n", $e->getTraceAsString());
        foreach (array_slice($traceLines, 0, 5) as $tl) {
            echo "     " . $tl . "\n";
        }
        echo "\n";
    }

    echo "{$colorRed}{$colorBold}FALHOU!{$colorReset} ";
    echo "{$totalTests} testes executados, {$failedCount} falharam, {$passedCount} passaram ({$totalDuration}s)\n";
    exit(1);
}

echo "{$colorGreen}{$colorBold}OK!{$colorReset} ";
echo "{$totalTests} testes executados com sucesso ({$totalDuration}s)\n";
exit(0);
