<?php

namespace App\Helpers;

class EnumHelper
{
    /**
     * Get all constants from a class
     *
     * @param string $class
     * @return array
     */
    public static function getConstants($class)
    {
        $reflection = new \ReflectionClass($class);
        return $reflection->getConstants();
    }

    /**
     * Get constant value by name
     *
     * @param string $class
     * @param string $name
     * @return mixed
     */
    public static function getConstant($class, $name)
    {
        $reflection = new \ReflectionClass($class);
        return $reflection->getConstant($name);
    }

    /**
     * Check if constant exists
     *
     * @param string $class
     * @param string $name
     * @return bool
     */
    public static function hasConstant($class, $name)
    {
        $reflection = new \ReflectionClass($class);
        return $reflection->hasConstant($name);
    }
}
