<?php

namespace App\Console\Commands;

use App\Services\Logo\SvgAssetImporter;
use Illuminate\Console\Command;

class ImportSvgAssetsCommand extends Command
{
    protected $signature = 'logoforge:import-svgs
        {path=storage/app/svg-library : Directory containing .svg vector files or category subfolders}
        {--category=abstract : Default category when not inside a named subfolder}
        {--styles=modern,bold : Comma-separated style slugs}
        {--industries=general : Comma-separated industry slugs}
        {--preserve-colors : Keep original multi-colour fills embedded in each SVG}
        {--premium : Mark imported symbols as premium}';

    protected $aliases = ['logoforge:import-assets'];

    protected $description = 'Bulk import CC0 or custom .svg, .png, and .webp logo illustrations into the Logoforge symbol library.';

    public function handle(SvgAssetImporter $importer): int
    {
        $rawPath = (string) $this->argument('path');
        $resolved = str_starts_with($rawPath, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $rawPath)
            ? $rawPath
            : base_path($rawPath);

        if (! is_dir($resolved)) {
            $this->error("Directory not found: {$resolved}");

            return self::FAILURE;
        }

        $styles = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('styles')))));
        $industries = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('industries')))));

        $result = $importer->importDirectory($resolved, [
            'category' => (string) $this->option('category'),
            'styles' => $styles ?: ['modern', 'bold'],
            'industries' => $industries ?: ['general'],
            'preserve_colors' => (bool) $this->option('preserve-colors'),
            'is_premium' => (bool) $this->option('premium'),
        ]);

        $this->info("Imported {$result['imported']} SVG assets (skipped {$result['skipped']}).");

        return self::SUCCESS;
    }
}
