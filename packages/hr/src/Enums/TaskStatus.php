<?php

namespace Nami\Hr\Enums;

enum TaskStatus : string
{
    case New = "new";
    case Transferred = "transferred";
    case Delay = 'delay';
    case Completed = 'completed';

    public function lang()
    {
        return __(trans("hr::hr.{$this->value}"));
    }
}
