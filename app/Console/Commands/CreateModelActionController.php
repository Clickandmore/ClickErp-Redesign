<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateModelActionController extends Command
{
    protected $signature = 'generate:files_from_migrations {--resources}';
    protected $description = 'Generate action classes for each model';

    public function handle()
    {
        $projectName = 'ingaz';
        $migrations = $this->getMigrations($projectName);
        $totalMigrations = count($migrations);

        $this->output->progressStart($totalMigrations);
        $this->output->write("\n");

        foreach ($migrations as $index => $migration) {
            $modelName = $this->extractModelName($migration);

            if ($modelName === null) {
                continue;
            }

            // Prevent creating model,action and controllers for the add_column_file
            if ($this->isCreateTableMigration($migration)) {

                $this->generateModel($modelName, $projectName);

                // Prevent creating action and controller to the translations tables
                if (!$this->isTranslationTable($modelName)) {
                    $this->callMakeActionCommand($modelName);
                    $this->generateController($modelName, $projectName);
                }
            }
        }

        if ($this->option('resources')) {
            $this->callMakeResourcesCommand();
        }

        $this->output->progressFinish();
        $this->info("\n 🙌 Models, Actions, and controllers generated successfully!");
    }

    protected function isTranslationTable($modelName)
    {
        return stripos($modelName, 'translation') !== false;
    }

    protected function isCreateTableMigration($migrationFileName)
    {
        // Extract the table name from the migration file name
        preg_match('/create_(\w+)_table/i', pathinfo($migrationFileName, PATHINFO_FILENAME), $matches);
        $tableName = $matches[1] ?? null;

        // Check if the table name starts with 'Add'
        return $tableName !== null;
    }


    protected function getMigrations($projectName)
    {
        $path = database_path("migrations/{$projectName}");

        $migrations = [];
        foreach (scandir($path) as $file) {
            $filePath = $path . DIRECTORY_SEPARATOR . $file;

            if (is_file($filePath) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $migrations[] = $file;
            }
        }

        return $migrations;
    }

    protected function extractModelName($migration)
    {
        preg_match('/create_(\w+)_table/i', pathinfo($migration, PATHINFO_FILENAME), $matches);

        $modelName = $matches[1] ?? null;

        return $modelName !== null ? Str::singular(Str::studly($modelName)) : null;
    }

    protected function callMakeActionCommand($modelActionName)
    {
        $this->call('make:action', [
            'model' => $modelActionName,
            'action' => $modelActionName,
        ]);
    }
    protected function callMakeResourcesCommand(){

        $this->call('generate:resources');
    }

    protected function generateModel($modelName, $projectName)
    {
        Artisan::call('make:model', ['name' => "{$projectName}\\{$modelName}"]);
    }

    protected function generateController($controllerName, $projectName)
    {
        Artisan::call("make:controller {$projectName}/{$controllerName}Controller --model={$projectName}/{$controllerName} --force");
        Artisan::call("make:request ".$projectName."/".$controllerName."Request");
    }
}
