<?php

declare(strict_types=1);

const MINIMUM_PERCENT = 80.0;

$coveragePath = $argv[1] ?? 'coverage.xml';
if (!is_file($coveragePath)) {
    fwrite(STDERR, "Coverage file not found: {$coveragePath}\n");
    exit(1);
}

$xml = simplexml_load_file($coveragePath);
if ($xml === false) {
    fwrite(STDERR, "Coverage file is not valid XML: {$coveragePath}\n");
    exit(1);
}

$sourceDirectory = realpath(__DIR__ . '/../src');
if ($sourceDirectory === false) {
    fwrite(STDERR, "Production source directory could not be resolved.\n");
    exit(1);
}

/** @var array<string, true> $expectedFiles */
$expectedFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
        $relativePath = 'src/' . str_replace('\\', '/', substr($file->getPathname(), strlen($sourceDirectory) + 1));
        $expectedFiles[$relativePath] = true;
    }
}
ksort($expectedFiles);

$fileNodes = $xml->xpath('//file');
if ($fileNodes === false || $fileNodes === []) {
    fwrite(STDERR, "Coverage file is missing file nodes: {$coveragePath}\n");
    exit(1);
}

/** @var array<string, float> $reportedFiles */
$reportedFiles = [];
foreach ($fileNodes as $fileNode) {
    $name = str_replace('\\', '/', (string) ($fileNode['name'] ?? ''));
    if (!preg_match('~(?:^|/)src/(.+\.php)$~', $name, $matches)) {
        continue;
    }

    $relativeName = 'src/' . $matches[1];
    $metricsNodes = $fileNode->xpath('./metrics');
    if ($metricsNodes === false || $metricsNodes === []) {
        fwrite(STDERR, "Coverage metrics are missing for {$relativeName}.\n");
        exit(1);
    }

    $metrics = $metricsNodes[0];
    $statements = (int) ($metrics['statements'] ?? 0);
    $coveredStatements = (int) ($metrics['coveredstatements'] ?? 0);
    $reportedFiles[$relativeName] = $statements === 0
        ? 100.0
        : ($coveredStatements / $statements) * 100;
}
ksort($reportedFiles);

$missingFiles = array_diff_key($expectedFiles, $reportedFiles);
$unexpectedFiles = array_diff_key($reportedFiles, $expectedFiles);
$offenders = array_filter(
    $reportedFiles,
    static fn (float $percent): bool => $percent < MINIMUM_PERCENT
);

if ($missingFiles !== [] || $unexpectedFiles !== [] || $offenders !== []) {
    fwrite(STDOUT, sprintf("Per-file coverage check failed. Minimum required: %.0f%%\n", MINIMUM_PERCENT));
    foreach (array_keys($missingFiles) as $filePath) {
        fwrite(STDOUT, "- {$filePath}: missing from coverage report\n");
    }
    foreach (array_keys($unexpectedFiles) as $filePath) {
        fwrite(STDOUT, "- {$filePath}: not present in the production source inventory\n");
    }
    foreach ($offenders as $filePath => $percent) {
        fwrite(STDOUT, sprintf("- %s: %.2f%%\n", $filePath, $percent));
    }
    exit(1);
}

fwrite(
    STDOUT,
    sprintf(
        "Per-file coverage check passed for %d production files at >= %.0f%%.\n",
        count($expectedFiles),
        MINIMUM_PERCENT
    )
);
