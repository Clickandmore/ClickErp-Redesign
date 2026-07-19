<?php

namespace Tests\Concerns;

use Tests\Support\Baseline;

trait AssertsAgainstBaseline
{
    /**
     * Fail on problems that are not already recorded in the baseline for $key.
     *
     * @param  list<string>  $problems
     */
    protected function assertNoNewProblems(string $key, array $problems, string $heading): void
    {
        sort($problems);

        if (Baseline::shouldUpdate() || ! Baseline::exists($key)) {
            Baseline::save($key, $problems);
            $this->addToAssertionCount(1);

            return;
        }

        $known = Baseline::load($key);

        $new = array_values(array_diff($problems, $known));
        $fixed = array_values(array_diff($known, $problems));

        if ($fixed !== []) {
            // Not a failure — just nudge the developer to shrink the baseline.
            fwrite(STDERR, sprintf(
                "\n[baseline:%s] %d recorded problem(s) no longer occur. Re-run with BASELINE_UPDATE=1 to lock in the fix.\n",
                $key,
                count($fixed)
            ));
        }

        $this->assertSame([], $new, sprintf(
            "%s — %d NEW problem(s) not in tests/baselines/%s.json:\n  - %s\n\n"
                . "If these are intentional, re-run with BASELINE_UPDATE=1 to record them.",
            $heading,
            count($new),
            $key,
            implode("\n  - ", $new)
        ));
    }
}
