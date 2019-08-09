<?php

namespace Ailixter\Gears\WatchedCall\Helpers;

/**
 * TODO Undocumented class
 */
class Subject implements SubjectInterface
{
    private $observers = [];

    /**
     * Attach callable observer.
     *
     * TODO Undocumented function long description
     *
     * @param callable $observer
     * @return void
     * @throws conditon
     **/
    public function attach(callable $observer): void
    {
        if ($this->check($observer)) {
            $this->observers[] = $observer;
        }
    }

    /**
     * Attach callable observer.
     *
     * TODO Undocumented function long description
     *
     * @param string $name
     * @param callable $observer
     * @return void
     * @throws conditon
     **/
    public function attachNamed(string $name, callable $observer): void
    {
        if ($this->check($observer)) {
            $this->observers[$name] = $observer;
        }
    }

    /**
     * Detach callable observer.
     *
     * @param string $name
     * @return callable
     */
    public function detachNamed(string $name): callable
    {
        $observer = $this->observers[$name];
        unset($this->observers[$name]);
        return $observer;
    }

    private function check(callable $observer): bool
    {
        if (in_array($observer, $this->observers, true)) {
            return false; // TODO
        }
        return true;
    }

    /**
     * TODO Undocumented function.
     *
     * @param mixed[] $args
     * @return bool
     */
    public function notify(...$args): bool
    {
        return $this(...$args);
    }

    public function __invoke(&...$args): bool
    {
        foreach ($this->observers as $callable) {
            if ($callable(...$args) === false) {
                return false;
            }
        }
        return true;
    }
}

