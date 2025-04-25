<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\ProgressBar;

class CustomMakeModelActionController extends Command
{
    protected $signature = 'generate:fast {model}';
    protected $description = 'Create a new migration, model, action, and resource controller';

    public function handle()
    {
        $name = $this->argument('model');
        $projectName = 'ingaz';

        // Create a console output instance
        $output = new ConsoleOutput();

        // Create a custom ProgressBar
        $progressBar = new ProgressBar($output);
        $progressBar->setFormat(
            "<info> %message% </info>\n %current%/%max% [%bar%]  - Total Time: %estimated%"
        );
        $progressBar->setMessage('🚀 Generating Files ...');

        // Set the maximum number of steps (20 in total for wider progress)
        $progressBar->setMaxSteps(20);

        $this->animateProgressBar($progressBar);

        try {
            // Step 1: Generate migration
            $this->generateMigration($name, $projectName, $progressBar);

            // Step 2: Generate model inside the specified folder
            $this->generateModel($name, $projectName, $progressBar);

            // Step 3: Generate action
            $this->generateAction($name, $progressBar);

            // Step 4: Generate resource controller
            $this->generateController($name, $projectName, $progressBar);

            $progressBar->finish();
            $this->info("\nAll steps completed successfully!");
        } catch (\Exception $e) {
            $progressBar->clear();
            $this->error("\nSorry 😢 but: {$e->getMessage()}");
        }
    }
    protected function generateMigration($name, $projectName, ProgressBar $progressBar)
    {
        $migrationName = "create{$name}s_table";
        $migrationPath = "database/migrations/{$projectName}";

        $this->slowProgress($progressBar);

        Artisan::call('make:migration', [
            'name' => $migrationName, '--path' => $migrationPath
        ]);
    }

    protected function generateModel($name, $projectName, ProgressBar $progressBar)
    {
        $this->slowProgress($progressBar);

        Artisan::call('make:model', ['name' => "{$projectName}\\{$name}"]);
    }

    protected function generateAction($name, ProgressBar $progressBar)
    {
        $this->slowProgress($progressBar);

        Artisan::call('make:action', ['model' => $name, 'action' => $name]);
    }

    protected function generateController($name, $projectName, ProgressBar $progressBar)
    {
        $this->slowProgress($progressBar);

        Artisan::call('make:controller', [
            'name' => "{$projectName}\\{$name}Controller",
            '--resource' => true,
        ]);
    }

    protected function animateProgressBar(ProgressBar $progressBar)
    {
        $frames = ['🌕', '🌖', '🌍', '🌘', '🌑', '🌒', '🌓', '🌔', '🌚', '🌝'];

        $progressBar->setMaxSteps(count($frames) - 1); // Adjusted to use the correct number of frames

        foreach ($frames as $frame) {
            $progressBar->setBarCharacter($frame);
            $progressBar->display();
            $this->slowProgress($progressBar);
            $progressBar->advance();
        }

        // Complete the progress bar at the end of animation
        $progressBar->finish();
    }

    protected function slowProgress(ProgressBar $progressBar)
    {
        usleep(1500000); // 1.5 second
    }
}
