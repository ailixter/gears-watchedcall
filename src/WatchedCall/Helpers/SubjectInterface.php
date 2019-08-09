<?php

namespace Ailixter\Gears\WatchedCall\Helpers;

/**
 * TODO Undocumented interface
 */
interface SubjectInterface
{
    public function attach(callable $observer): void;
    public function attachNamed(string $name, callable $observer): void;
    public function detachNamed(string $name): callable;
    public function notify(...$args): bool;
}

