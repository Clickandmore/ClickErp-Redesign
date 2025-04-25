<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeAction extends Command
{
    protected $signature = 'make:action {model} {action}';
    protected $description = 'Create a new action class for a model';

    public function handle()
    {
        $modelName = $this->argument('model');
        $actionName = $this->argument('action');
        $projectName = 'ingaz';

        $namespace = "App\Http\Actions\\{$projectName}";
        $actionClassName = $this->getActionClassName($actionName);
        $fullPath = $this->getActionClassFullPath($projectName, $actionClassName);

        if ($this->fileExists($fullPath)) {
            $this->line("Hint 🚨 : Class {$namespace}\\{$actionClassName} already exists!");
            return;
        }

        $this->createDirectoryIfNeeded($fullPath);

        $modelFullName = $this->getModelFullName($modelName, $projectName);
        $content = $this->generateActionClassContent($namespace, $modelFullName, $modelName, $actionClassName);
        $this->writeToFile($fullPath, $content);

        $this->info("Done 🟢: Action class '{$actionName}' for model '{$modelName}' generated successfully!");
    }

    protected function fileExists($fullPath)
    {
        return File::exists($fullPath);
    }

    protected function getActionClassName($actionName)
    {
        return $actionName . 'Action';
    }

    protected function getActionClassFullPath($projectName, $actionClassName)
    {
        return app_path("Http/Actions/{$projectName}/{$actionClassName}.php");
    }

    protected function createDirectoryIfNeeded($fullPath)
    {
        File::makeDirectory(dirname($fullPath), 0755, true, true);
    }

    protected function classExists($namespace, $actionClassName)
    {
        return File::exists(app_path("Http/Actions/{$namespace}/{$actionClassName}.php"));
    }

    protected function getModelFullName($modelName, $projectName)
    {
        return "{$projectName}\\{$modelName}";
    }

    protected function generateActionClassContent($namespace, $modelFullName, $modelName, $actionClassName)
    {
        return "<?php\n\nnamespace {$namespace};\n\nuse App\Http\Actions\MainAction;\nuse App\Models\\{$modelFullName};\n\nclass {$actionClassName} extends MainAction\n{\n"
            . "    public function __construct({$modelName} \$model)\n"
            . "    {\n"
            . "        \$this->model = \$model;\n"
            . "        \$this->fileFolder = 'images/{$modelName}/';\n"
            . "    }\n"
            . "}\n";
    }

    protected function writeToFile($fullPath, $content)
    {
        File::put($fullPath, $content);
    }
}

