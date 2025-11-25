<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanMissingProductImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:clean-missing-images {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove product image records that point to non-existent files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Checking for missing product images...');
        $this->newLine();

        $images = ProductImage::all();
        $missingImages = [];
        $existingImages = [];

        foreach ($images as $image) {
            $path = $image->image_url;
            
            // Skip external URLs
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $existingImages[] = $image;
                continue;
            }
            
            // Check if file exists in storage
            if (Storage::disk('public')->exists($path)) {
                $existingImages[] = $image;
                $this->line("✓ <fg=green>EXISTS:</> {$path}");
            } else {
                $missingImages[] = $image;
                $this->line("✗ <fg=red>MISSING:</> {$path} (Product ID: {$image->product_id}, Image ID: {$image->id})");
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->line("Existing images: " . count($existingImages));
        $this->line("Missing images: " . count($missingImages));
        $this->newLine();

        if (count($missingImages) > 0) {
            if ($dryRun) {
                $this->warn('DRY RUN: No records were deleted. Run without --dry-run to actually delete orphaned records.');
            } else {
                if ($this->confirm('Do you want to delete these orphaned image records?', true)) {
                    $bar = $this->output->createProgressBar(count($missingImages));
                    $bar->start();

                    foreach ($missingImages as $image) {
                        $image->delete();
                        $bar->advance();
                    }

                    $bar->finish();
                    $this->newLine(2);
                    $this->info('Successfully deleted ' . count($missingImages) . ' orphaned image records.');
                } else {
                    $this->info('Operation cancelled.');
                }
            }
        } else {
            $this->info('No missing images found. All product images are valid!');
        }

        return Command::SUCCESS;
    }
}
