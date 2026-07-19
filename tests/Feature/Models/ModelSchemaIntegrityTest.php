<?php

namespace Tests\Feature\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * Project-wide guardrails over the model layer.
 *
 * These catch the class of bug where a model silently points at a table that does not
 * exist (Eloquent's pluraliser and the real migration disagree) or a relation names a
 * class that was moved or never created — both fatal only at runtime, on whichever
 * screen happens to touch them.
 */
class ModelSchemaIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Models whose table genuinely lives elsewhere (other connection, view, not yet
     * migrated). Add with a reason rather than deleting an assertion.
     */
    private const TABLE_EXEMPT = [
        // Dead models: no migration anywhere in the repo creates their table, and nothing
        // references them. They are candidates for deletion, not for a schema fix.
        'App\Models\TEst' => 'scratch model (typo\'d name), unreferenced',
        'App\Models\Nami\Backup' => 'legacy Nami package model, table never migrated',
        'App\Models\Nami\Command' => 'legacy Nami package model, table never migrated',
        'App\Models\Nami\MainSetting' => 'legacy Nami package model, table never migrated',
        'App\Models\Nami\MainSettingTranslation' => 'legacy Nami package model, table never migrated',
        'App\Models\ingaz\NotificationsTranslation' => 'translation table never migrated',
        'App\Models\office\OfficeTranslation' => 'translation table never migrated',
        'App\Models\ingaz\RoleUser' => 'laratrust leftover; project migrated to spatie/laravel-permission',
    ];

    /** @test */
    public function every_model_resolves_to_a_table_that_exists(): void
    {
        $problems = [];

        foreach ($this->eloquentModels() as $class) {
            if (array_key_exists($class, self::TABLE_EXEMPT)) {
                continue;
            }

            try {
                $model = new $class;
            } catch (\Throwable $e) {
                $problems[] = "{$class} cannot be instantiated: {$e->getMessage()}";
                continue;
            }

            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                $problems[] = "{$class} resolves to missing table [{$table}]";
            }
        }

        $this->assertSame([], $problems, $this->report('Models pointing at a table that does not exist', $problems));
    }

    /** @test */
    public function every_relation_points_at_a_class_that_exists(): void
    {
        $problems = [];

        foreach ($this->eloquentModels() as $class) {
            try {
                $model = new $class;
            } catch (\Throwable $e) {
                continue; // already reported by the table test
            }

            foreach ($this->relationMethods($class) as $method) {
                try {
                    $relation = $model->{$method->getName()}();
                } catch (\Throwable $e) {
                    $problems[] = "{$class}::{$method->getName()}() — {$e->getMessage()}";
                    continue;
                }

                if (! $relation instanceof Relation) {
                    continue;
                }

                $related = get_class($relation->getRelated());

                if (! class_exists($related)) {
                    $problems[] = "{$class}::{$method->getName()}() relates to missing class [{$related}]";
                }
            }
        }

        $this->assertSame([], $problems, $this->report('Broken model relations', $problems));
    }

    /**
     * Methods that declare an Eloquent relation return type. Relying on the declared
     * type keeps us from invoking arbitrary model methods with side effects.
     *
     * @return list<ReflectionMethod>
     */
    private function relationMethods(string $class): array
    {
        $methods = [];

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $type = $method->getReturnType();

            if ($type instanceof ReflectionNamedType
                && ! $type->isBuiltin()
                && is_subclass_of($type->getName(), Relation::class)) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    /**
     * @return list<class-string<Model>>
     */
    private function eloquentModels(): array
    {
        $models = [];
        $root = app_path('Models');

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace([$root . DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
            $class = 'App\\Models\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
                continue;
            }

            if ($reflection->isSubclassOf(Model::class)) {
                $models[] = $class;
            }
        }

        sort($models);

        return $models;
    }

    private function report(string $heading, array $problems): string
    {
        return $heading . ":\n  - " . implode("\n  - ", $problems);
    }
}
