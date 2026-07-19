<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Tests\Concerns\AssertsAgainstBaseline;
use Tests\TestCase;

/**
 * Project-wide guardrails over the routing layer. These are cheap, need no database,
 * and catch the "works until somebody opens that page" class of failure: a route
 * pointing at a controller action that no longer exists, a duplicated route name
 * silently shadowing another, or a Blade view calling route() with a name nobody
 * registered.
 */
class RouteIntegrityTest extends TestCase
{
    use AssertsAgainstBaseline;

    /** @test */
    public function every_route_points_at_a_controller_action_that_exists(): void
    {
        $problems = [];

        foreach ($this->routes() as $route) {
            $action = $route->getActionName();

            if ($action === 'Closure' || ! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);

            if (! class_exists($controller)) {
                $problems[] = $this->describe($route) . " => missing controller [{$controller}]";
                continue;
            }

            if (! method_exists($controller, $method) && ! method_exists($controller, '__call')) {
                $problems[] = $this->describe($route) . " => {$controller} has no method [{$method}]";
            }
        }

        $this->assertNoNewProblems('routes-missing-actions', $problems, 'Routes bound to missing controller actions');
    }

    /** @test */
    public function no_two_routes_share_a_name(): void
    {
        $seen = [];
        $duplicates = [];

        foreach ($this->routes() as $route) {
            $name = $route->getName();

            if (! $name) {
                continue;
            }

            if (isset($seen[$name])) {
                $duplicates[] = "[{$name}] {$seen[$name]}  <->  " . $this->describe($route);
            }

            $seen[$name] = $this->describe($route);
        }

        $this->assertNoNewProblems(
            'routes-duplicate-names',
            $duplicates,
            'Duplicate route names — the later registration silently wins'
        );
    }

    /** @test */
    public function every_route_name_used_in_a_blade_view_is_registered(): void
    {
        $registered = collect($this->routes())
            ->map(fn (Route $route) => $route->getName())
            ->filter()
            ->flip();

        $problems = [];

        foreach ($this->bladeFiles() as $file) {
            $contents = File::get($file);

            // Only literal single-argument route('name') calls; anything built from a
            // variable cannot be checked statically.
            preg_match_all("/\broute\(\s*'([a-zA-Z0-9_.\-]+)'/", $contents, $matches);

            foreach (array_unique($matches[1]) as $name) {
                if (! $registered->has($name)) {
                    $problems[] = str_replace(base_path() . '/', '', $file) . " => route('{$name}')";
                }
            }
        }

        $this->assertNoNewProblems('views-unknown-routes', $problems, 'Views referencing unregistered route names');
    }

    /** @test */
    public function every_blade_view_compiles(): void
    {
        $problems = [];

        foreach ($this->bladeFiles() as $file) {
            try {
                Blade::compileString(File::get($file));
            } catch (\Throwable $e) {
                $problems[] = str_replace(base_path() . '/', '', $file) . ' => ' . $e->getMessage();
            }
        }

        $this->assertSame([], $problems, $this->report('Blade templates that fail to compile', $problems));
    }

    /**
     * @return list<Route>
     */
    private function routes(): array
    {
        return array_values(iterator_to_array(app('router')->getRoutes()));
    }

    /**
     * @return list<string>
     */
    private function bladeFiles(): array
    {
        $files = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private function describe(Route $route): string
    {
        return implode('|', $route->methods()) . ' /' . $route->uri();
    }

    private function report(string $heading, array $problems): string
    {
        return $heading . " (" . count($problems) . "):\n  - " . implode("\n  - ", $problems);
    }
}
