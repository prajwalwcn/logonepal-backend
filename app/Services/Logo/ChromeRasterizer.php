<?php

namespace App\Services\Logo;

use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Rasterises an SVG to PNG/PDF with headless Chrome.
 *
 * Chrome is used instead of a PHP image extension because it renders the same
 * web fonts the editor shows the user. When no binary is available the export
 * pipeline degrades to SVG only, and the API reports the missing format rather
 * than returning a broken file.
 */
class ChromeRasterizer
{
    public function __construct(private readonly SvgRenderingService $renderer)
    {
    }

    public function available(): bool
    {
        $binary = $this->binary();

        return $binary !== null && is_file($binary);
    }

    /**
     * @param  array<int, string>  $fontFamilies
     */
    public function png(string $svg, array $fontFamilies, int $width, ?string $background = '#FFFFFF'): string
    {
        $height = (int) round($width * (DesignDocument::CANVAS_HEIGHT / DesignDocument::CANVAS_WIDTH));

        return $this->rasterize($svg, $fontFamilies, $width, $height, $background, 'png');
    }

    /**
     * @param  array<int, string>  $fontFamilies
     */
    public function pdf(string $svg, array $fontFamilies, ?string $background = '#FFFFFF'): string
    {
        return $this->rasterize(
            $svg,
            $fontFamilies,
            DesignDocument::CANVAS_WIDTH * 2,
            DesignDocument::CANVAS_HEIGHT * 2,
            $background,
            'pdf'
        );
    }

    /**
     * Rasterise any canvas size (design-format exports).
     *
     * @param  array<int, string>  $fontFamilies
     */
    public function pngSized(string $svg, array $fontFamilies, int $width, int $height, ?string $background = '#FFFFFF'): string
    {
        $width = max(64, min(4096, $width));
        $height = max(64, min(4096, $height));

        return $this->rasterize($svg, $fontFamilies, $width, $height, $background, 'png');
    }

    /**
     * @param  array<int, string>  $fontFamilies
     */
    public function pdfSized(string $svg, array $fontFamilies, int $width, int $height, ?string $background = '#FFFFFF'): string
    {
        $width = max(64, min(4096, $width));
        $height = max(64, min(4096, $height));

        return $this->rasterize($svg, $fontFamilies, $width, $height, $background, 'pdf');
    }

    private function rasterize(string $svg, array $fontFamilies, int $width, int $height, ?string $background, string $format): string
    {
        if (! $this->available()) {
            throw new RuntimeException('No headless browser is available for raster export.');
        }

        $directory = storage_path('app/tmp');
        @mkdir($directory, 0775, true);

        $token = bin2hex(random_bytes(8));
        $htmlPath = $directory.DIRECTORY_SEPARATOR."{$token}.html";
        $outputPath = $directory.DIRECTORY_SEPARATOR."{$token}.{$format}";

        $svg = $this->inlineLocalImages($svg);
        $document = $this->renderer->renderHtmlDocument($svg, $fontFamilies, $background);
        $document = $this->fitDocument($document, $width, $height, $format);

        file_put_contents($htmlPath, $document);

        $profileDir = $directory.DIRECTORY_SEPARATOR.'chrome-profile';
        @mkdir($profileDir, 0775, true);

        $commonArgs = [
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--hide-scrollbars',
            '--disable-crash-reporter',
            '--disable-breakpad',
            '--no-crash-upload',
            '--disable-dev-shm-usage',
            '--user-data-dir='.$profileDir,
            '--run-all-compositor-stages-before-draw',
        ];

        try {
            if ($format === 'pdf') {
                $arguments = array_merge($commonArgs, [
                    '--no-pdf-header-footer',
                    '--print-to-pdf='.$outputPath,
                    '--virtual-time-budget=4000',
                    'file:///'.str_replace('\\', '/', $htmlPath),
                ]);
            } else {
                $arguments = array_merge($commonArgs, [
                    '--force-device-scale-factor=1',
                    '--window-size='.$width.','.$height,
                    '--screenshot='.$outputPath,
                    '--virtual-time-budget=4000',
                    'file:///'.str_replace('\\', '/', $htmlPath),
                ]);

                if ($background === null) {
                    array_unshift($arguments, '--default-background-color=00000000');
                }
            }

            $process = new Process(array_merge([$this->binary()], $arguments));
            $process->setTimeout((int) config('services.chrome.timeout', 45));
            $process->run();

            if (! is_file($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('Headless browser produced no output: '.mb_substr($process->getErrorOutput(), 0, 300));
            }

            return (string) file_get_contents($outputPath);
        } finally {
            @unlink($htmlPath);
            @unlink($outputPath);
        }
    }

    private function inlineLocalImages(string $svg): string
    {
        return (string) preg_replace_callback(
            '/(<image\b[^>]*?\b(?:xlink:)?href=)(["\'])([^"\']+)\2/i',
            function (array $matches): string {
                $prefix = $matches[1];
                $quote = $matches[2];
                $url = $matches[3];

                if (str_starts_with($url, 'data:')) {
                    return $matches[0];
                }

                $path = parse_url($url, PHP_URL_PATH) ?: $url;
                $candidates = [];

                if (str_starts_with($path, '/storage/')) {
                    $rel = substr($path, strlen('/storage/'));
                    $candidates[] = storage_path('app/public/'.ltrim($rel, '/'));
                    $candidates[] = public_path('storage/'.ltrim($rel, '/'));
                } elseif (str_starts_with($path, '/')) {
                    $rel = ltrim($path, '/');
                    $candidates[] = base_path('../web/public/'.$rel);
                    $candidates[] = public_path($rel);
                }

                foreach ($candidates as $file) {
                    if (is_file($file) && is_readable($file)) {
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $mime = match ($ext) {
                            'png' => 'image/png',
                            'jpg', 'jpeg' => 'image/jpeg',
                            'webp' => 'image/webp',
                            'svg' => 'image/svg+xml',
                            default => 'application/octet-stream',
                        };
                        $raw = @file_get_contents($file);
                        if ($raw !== false && $raw !== '') {
                            return $prefix.$quote.'data:'.$mime.';base64,'.base64_encode($raw).$quote;
                        }
                    }
                }

                if (preg_match('#^https?://#i', $url)) {
                    $host = (string) parse_url($url, PHP_URL_HOST);
                    if ($host !== '127.0.0.1' && $host !== 'localhost') {
                        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
                        $raw = @file_get_contents($url, false, $ctx);
                        if ($raw !== false && $raw !== '') {
                            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                            $mime = match ($ext) {
                                'png' => 'image/png',
                                'jpg', 'jpeg' => 'image/jpeg',
                                'webp' => 'image/webp',
                                'svg' => 'image/svg+xml',
                                default => 'application/octet-stream',
                            };
                            return $prefix.$quote.'data:'.$mime.';base64,'.base64_encode($raw).$quote;
                        }
                    }
                }

                return $matches[0];
            },
            $svg
        );
    }

    private function fitDocument(string $html, int $width, int $height, string $format): string
    {
        $page = $format === 'pdf'
            ? '@page{size:'.($width / 2).'px '.($height / 2).'px;margin:0;}'
            : '';

        return str_replace(
            '</style>',
            $page.'body{width:'.$width.'px;height:'.$height.'px;overflow:hidden;}svg{width:'.$width.'px;height:'.$height.'px;}</style>',
            $html
        );
    }

    private function binary(): ?string
    {
        $configured = config('services.chrome.binary');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        foreach ([
            '/usr/bin/google-chrome', '/usr/bin/chromium-browser', '/usr/bin/chromium',
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            'C:/Program Files/Google/Chrome/Application/chrome.exe',
            'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
        ] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function diagnose(): array
    {
        try {
            return ['available' => $this->available(), 'binary' => $this->binary()];
        } catch (Throwable $exception) {
            return ['available' => false, 'binary' => null, 'error' => $exception->getMessage()];
        }
    }
}
