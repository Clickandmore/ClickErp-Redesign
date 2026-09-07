<?php

namespace App\Support\Audit;

class ExecutionContext
{
    private array $commands = [];

    public function start(?string $command): void
    {
        $this->commands[] = $command;
    }

    public function finish(): void
    {
        array_pop($this->commands);
    }

    public function command(): ?string
    {
        return end($this->commands) ?: null;
    }
}
