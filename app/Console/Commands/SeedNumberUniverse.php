<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NumberClassificationService;

class SeedNumberUniverse extends Command
{
    protected $signature = 'xsmb:seed-numbers';
    protected $description = 'Seed the number_universe table with all 100 numbers (00-99) and their classifications';

    public function handle()
    {
        $this->info('Seeding number_universe table...');

        $service = new NumberClassificationService();
        $count = $service->seedDatabase();

        $this->info("✅ Done! Seeded {$count} numbers into number_universe table.");
        return Command::SUCCESS;
    }
}
