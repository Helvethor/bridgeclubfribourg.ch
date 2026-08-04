<?php

declare(strict_types=1);

namespace App\Service;

use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpKernel\KernelInterface;

class PDFGenerationService
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly string $pdfServiceUrl = '',
    )
    {
    }

    /**
     * @return array{file: string, filename: string}
     */
    public function generate(string $url): array
    {
        @set_time_limit(120);

        $requestStartedAt = microtime(true);

        $path     = parse_url($url, PHP_URL_PATH) ?? '';
        $filename = ltrim(str_replace('/', '-', $path), '-') . '-' . date('YmdHis') . '.pdf';
        $pdfDir   = $this->kernel->getProjectDir() . '/var/pdf';
        $file     = $pdfDir . '/' . $filename;
        $chromeDir = $this->kernel->getProjectDir() . '/var/chromium';
        $chromeSessionDir = $chromeDir . '/sessions';
        $chromeCrashDumpsDir = $chromeDir . '/crash-dumps';
        $chromeCacheDir = $chromeDir . '/cache';

        if (!is_dir($pdfDir) && !mkdir($pdfDir, 0775, true) && !is_dir($pdfDir)) {
            throw new \RuntimeException(sprintf('Unable to create PDF directory: %s', $pdfDir));
        }

        if (!is_dir($chromeSessionDir) && !mkdir($chromeSessionDir, 0775, true) && !is_dir($chromeSessionDir)) {
            throw new \RuntimeException(sprintf('Unable to create Chrome sessions directory: %s', $chromeSessionDir));
        }

        if (!is_dir($chromeCrashDumpsDir) && !mkdir($chromeCrashDumpsDir, 0775, true) && !is_dir($chromeCrashDumpsDir)) {
            throw new \RuntimeException(sprintf('Unable to create Chrome crash-dumps directory: %s', $chromeCrashDumpsDir));
        }

        if (!is_dir($chromeCacheDir) && !mkdir($chromeCacheDir, 0775, true) && !is_dir($chromeCacheDir)) {
            throw new \RuntimeException(sprintf('Unable to create Chrome cache directory: %s', $chromeCacheDir));
        }

        if ($this->pdfServiceUrl !== '') {
            $remoteResult = $this->generateWithPersistentPdfService($url, $file, $requestStartedAt);
            if ($remoteResult !== null) {
                return [
                    'file' => $file,
                    'filename' => $filename,
                ];
            }

            error_log('[PDF] remote generation unavailable, falling back to local Browsershot');
        }

        $errors = [];
        $attemptTimings = [];
        $selectedCandidate = null;
        foreach ($this->buildPdfTargetCandidates($url) as $candidate) {
            $attemptStartedAt = microtime(true);
            $attemptUserDataDir = $chromeSessionDir . '/profile-' . str_replace('.', '', uniqid('', true));
            $profileReadyAt = null;
            $saveStartedAt = null;
            $saveDurationMs = null;
            $attemptErrorMessage = null;

            if (!mkdir($attemptUserDataDir, 0775, true) && !is_dir($attemptUserDataDir)) {
                throw new \RuntimeException(sprintf('Unable to create Chrome attempt profile directory: %s', $attemptUserDataDir));
            }

            $profileReadyAt = microtime(true);

            try {
                @unlink($file);

                $candidateHost = parse_url($candidate, PHP_URL_HOST);
                $isLocalCandidateHost = $candidateHost !== null && in_array($candidateHost, ['localhost', '127.0.0.1', '::1'], true);

                $browsershot = Browsershot::url($candidate)
                    ->setNodeBinary('/usr/bin/node')
                    ->setNodeModulePath('/usr/local/lib/node_modules')
                    ->setChromePath('/usr/lib/chromium/chromium')
                    ->timeout(25)
                    ->disableJavascript()
                    ->setOption('emulateMedia', 'print')
                    ->setOption('waitUntil', 'domcontentloaded')
                    ->setEnvironmentOptions([
                        'HOME' => $chromeDir,
                        'XDG_CACHE_HOME' => $chromeCacheDir,
                        'XDG_CONFIG_HOME' => '/config',
                        'XDG_DATA_HOME' => '/data',
                    ])
                    ->setOption('args', [
                        '--no-sandbox',
                        '--disable-setuid-sandbox',
                        '--disable-dev-shm-usage',
                        '--disable-crashpad-for-testing',
                        '--disable-crash-reporter',
                        '--disable-breakpad',
                        '--no-crash-upload',
                        '--single-process',
                        '--no-zygote',
                        '--user-data-dir=' . $attemptUserDataDir,
                        '--crash-dumps-dir=' . $chromeCrashDumpsDir,
                    ])
                    ->noSandbox();

                if (str_starts_with($candidate, 'https://') && ($this->kernel->getEnvironment() !== 'prod' || $isLocalCandidateHost)) {
                    $browsershot
                        ->ignoreHttpsErrors()
                        ->addChromiumArguments(['ignore-certificate-errors' => true]);
                }

                $saveStartedAt = microtime(true);
                $browsershot
                    ->format('A4')
                    ->showBackground()
                    ->save($file);
                $saveDurationMs = (microtime(true) - $saveStartedAt) * 1000;

                if (is_file($file)) {
                    $selectedCandidate = $candidate;
                    $this->deleteDirectory($attemptUserDataDir);
                    break;
                }
            } catch (\Throwable $exception) {
                $attemptErrorMessage = $exception->getMessage();
                $errors[] = sprintf('target=%s message=%s', $candidate, $exception->getMessage());
                error_log('[PDF] generation failed: target=' . $candidate . ' message=' . $exception->getMessage());
            } finally {
                $attemptFinishedAt = microtime(true);
                $attemptDurationMs = ($attemptFinishedAt - $attemptStartedAt) * 1000;
                $profileSetupMs = $profileReadyAt !== null ? ($profileReadyAt - $attemptStartedAt) * 1000 : 0.0;
                $preSaveMs = $saveStartedAt !== null && $profileReadyAt !== null ? ($saveStartedAt - $profileReadyAt) * 1000 : 0.0;
                $postSaveMs = $saveDurationMs !== null ? max(0.0, $attemptDurationMs - $profileSetupMs - $preSaveMs - $saveDurationMs) : 0.0;

                $attemptTimings[] = [
                    'candidate' => $candidate,
                    'duration_ms' => $attemptDurationMs,
                    'profile_setup_ms' => $profileSetupMs,
                    'pre_save_ms' => $preSaveMs,
                    'save_ms' => $saveDurationMs,
                    'post_save_ms' => $postSaveMs,
                    'success' => is_file($file),
                    'error' => $attemptErrorMessage,
                ];

                error_log(sprintf(
                    '[PDF] timing attempt candidate=%s success=%s duration_ms=%.2f profile_setup_ms=%.2f pre_save_ms=%.2f save_ms=%s post_save_ms=%.2f',
                    $candidate,
                    is_file($file) ? 'yes' : 'no',
                    $attemptDurationMs,
                    $profileSetupMs,
                    $preSaveMs,
                    $saveDurationMs !== null ? sprintf('%.2f', $saveDurationMs) : 'n/a',
                    $postSaveMs
                ));

                $this->deleteDirectory($attemptUserDataDir);
            }
        }

        $totalDurationMs = (microtime(true) - $requestStartedAt) * 1000;

        if (count($attemptTimings) > 0) {
            $profileSetupTotalMs = 0.0;
            $preSaveTotalMs = 0.0;
            $saveTotalMs = 0.0;
            $postSaveTotalMs = 0.0;

            foreach ($attemptTimings as $timing) {
                $profileSetupTotalMs += (float) $timing['profile_setup_ms'];
                $preSaveTotalMs += (float) $timing['pre_save_ms'];
                $saveTotalMs += (float) ($timing['save_ms'] ?? 0.0);
                $postSaveTotalMs += (float) $timing['post_save_ms'];
            }

            $safeTotal = max($totalDurationMs, 1.0);
            error_log(sprintf(
                '[PDF] timing summary selected=%s attempts=%d total_ms=%.2f profile_setup_ms=%.2f profile_setup_pct=%.2f pre_save_ms=%.2f pre_save_pct=%.2f save_ms=%.2f save_pct=%.2f post_save_ms=%.2f post_save_pct=%.2f',
                $selectedCandidate ?? 'none',
                count($attemptTimings),
                $totalDurationMs,
                $profileSetupTotalMs,
                ($profileSetupTotalMs / $safeTotal) * 100,
                $preSaveTotalMs,
                ($preSaveTotalMs / $safeTotal) * 100,
                $saveTotalMs,
                ($saveTotalMs / $safeTotal) * 100,
                $postSaveTotalMs,
                ($postSaveTotalMs / $safeTotal) * 100
            ));
        }

        if (!is_file($file)) {
            throw new \RuntimeException('PDF generation failed. ' . implode(' | ', $errors));
        }

        return [
            'file' => $file,
            'filename' => $filename,
        ];
    }

    private function generateWithPersistentPdfService(string $url, string $file, float $requestStartedAt): ?array
    {
        $errors = [];
        $attemptTimings = [];
        $selectedCandidate = null;
        $endpoint = rtrim($this->pdfServiceUrl, '/') . '/forms/chromium/convert/url';

        foreach ($this->buildPdfTargetCandidates($url) as $candidate) {
            $attemptStartedAt = microtime(true);
            $responseStartedAt = null;
            $writeStartedAt = null;
            $responseDurationMs = null;
            $writeDurationMs = null;
            $attemptErrorMessage = null;

            try {
                @unlink($file);

                $responseStartedAt = microtime(true);
                $remoteResponse = $this->requestPdfFromPersistentService($endpoint, $candidate);
                $responseDurationMs = (microtime(true) - $responseStartedAt) * 1000;

                if ($remoteResponse['status_code'] < 200 || $remoteResponse['status_code'] >= 300) {
                    throw new \RuntimeException(sprintf(
                        'Persistent PDF service returned HTTP %d: %s',
                        $remoteResponse['status_code'],
                        substr($remoteResponse['body'], 0, 300)
                    ));
                }

                $writeStartedAt = microtime(true);
                if (file_put_contents($file, $remoteResponse['body']) === false) {
                    throw new \RuntimeException(sprintf('Unable to write generated PDF to %s', $file));
                }
                $writeDurationMs = (microtime(true) - $writeStartedAt) * 1000;

                if (is_file($file) && filesize($file) > 0) {
                    $selectedCandidate = $candidate;
                    break;
                }

                throw new \RuntimeException('Persistent PDF service returned an empty PDF file');
            } catch (\Throwable $exception) {
                $attemptErrorMessage = $exception->getMessage();
                $errors[] = sprintf('target=%s message=%s', $candidate, $exception->getMessage());
                error_log('[PDF] remote generation failed: target=' . $candidate . ' message=' . $exception->getMessage());
            } finally {
                $attemptFinishedAt = microtime(true);
                $attemptDurationMs = ($attemptFinishedAt - $attemptStartedAt) * 1000;
                $preRequestMs = $responseStartedAt !== null ? ($responseStartedAt - $attemptStartedAt) * 1000 : 0.0;
                $postRequestMs = $responseDurationMs !== null ? max(0.0, $attemptDurationMs - $preRequestMs - $responseDurationMs - (float) ($writeDurationMs ?? 0.0)) : 0.0;

                $attemptTimings[] = [
                    'candidate' => $candidate,
                    'duration_ms' => $attemptDurationMs,
                    'pre_request_ms' => $preRequestMs,
                    'request_ms' => $responseDurationMs,
                    'write_ms' => $writeDurationMs,
                    'post_request_ms' => $postRequestMs,
                    'success' => is_file($file),
                    'error' => $attemptErrorMessage,
                ];

                error_log(sprintf(
                    '[PDF] timing attempt mode=remote candidate=%s success=%s duration_ms=%.2f pre_request_ms=%.2f request_ms=%s write_ms=%s post_request_ms=%.2f',
                    $candidate,
                    is_file($file) ? 'yes' : 'no',
                    $attemptDurationMs,
                    $preRequestMs,
                    $responseDurationMs !== null ? sprintf('%.2f', $responseDurationMs) : 'n/a',
                    $writeDurationMs !== null ? sprintf('%.2f', $writeDurationMs) : 'n/a',
                    $postRequestMs,
                ));
            }
        }

        $totalDurationMs = (microtime(true) - $requestStartedAt) * 1000;
        if (count($attemptTimings) > 0) {
            $preRequestTotalMs = 0.0;
            $requestTotalMs = 0.0;
            $writeTotalMs = 0.0;
            $postRequestTotalMs = 0.0;

            foreach ($attemptTimings as $timing) {
                $preRequestTotalMs += (float) $timing['pre_request_ms'];
                $requestTotalMs += (float) ($timing['request_ms'] ?? 0.0);
                $writeTotalMs += (float) ($timing['write_ms'] ?? 0.0);
                $postRequestTotalMs += (float) $timing['post_request_ms'];
            }

            $safeTotal = max($totalDurationMs, 1.0);
            error_log(sprintf(
                '[PDF] timing summary mode=remote selected=%s attempts=%d total_ms=%.2f pre_request_ms=%.2f pre_request_pct=%.2f request_ms=%.2f request_pct=%.2f write_ms=%.2f write_pct=%.2f post_request_ms=%.2f post_request_pct=%.2f',
                $selectedCandidate ?? 'none',
                count($attemptTimings),
                $totalDurationMs,
                $preRequestTotalMs,
                ($preRequestTotalMs / $safeTotal) * 100,
                $requestTotalMs,
                ($requestTotalMs / $safeTotal) * 100,
                $writeTotalMs,
                ($writeTotalMs / $safeTotal) * 100,
                $postRequestTotalMs,
                ($postRequestTotalMs / $safeTotal) * 100,
            ));
        }

        if ($selectedCandidate === null || !is_file($file)) {
            return null;
        }

        return [
            'file' => $file,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{status_code: int, body: string}
     */
    private function requestPdfFromPersistentService(string $endpoint, string $targetUrl): array
    {
        $ch = curl_init($endpoint);
        if ($ch === false) {
            throw new \RuntimeException('Unable to initialize cURL for persistent PDF service request');
        }

        $postFields = [
            'url' => $targetUrl,
            'landscape' => 'false',
            'printBackground' => 'true',
            'waitDelay' => '100ms',
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/pdf',
            ],
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Persistent PDF service request failed: ' . $error);
        }

        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return [
            'status_code' => (int) $statusCode,
            'body' => $body,
        ];
    }

    /**
     * @return list<string>
     */
    private function buildPdfTargetCandidates(string $url): array
    {
        $candidates = [$url];
        $host       = parse_url($url, PHP_URL_HOST);
        $path       = parse_url($url, PHP_URL_PATH) ?: '/';
        $query      = parse_url($url, PHP_URL_QUERY);
        $fragment   = parse_url($url, PHP_URL_FRAGMENT);
        $localHosts = ['bridgeclubfribourg.ch', 'www.bridgeclubfribourg.ch', 'localhost', '127.0.0.1', '::1'];

        if ($host !== null && in_array($host, $localHosts, true)) {
            $suffix = $path;
            if ($query !== null && $query !== '') {
                $suffix .= '?' . $query;
            }
            if ($fragment !== null && $fragment !== '') {
                $suffix .= '#' . $fragment;
            }

            // Try proven in-container targets first.
            if ($this->kernel->getEnvironment() === 'prod') {
                $candidates = [
                    'http://php' . $suffix,
                    $url,
                ];
            } else {
                $candidates = [
                    $url,
                    'https://localhost' . $suffix,
                    'http://php' . $suffix,
                ];

                // Keep loopback fallbacks for dev-like environments where these are reachable.
                $candidates[] = 'https://127.0.0.1' . $suffix;
                $candidates[] = 'http://127.0.0.1' . $suffix;
            }
        }

        return array_values(array_unique($candidates));
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($path);
    }
}
