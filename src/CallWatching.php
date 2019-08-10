<?php

namespace Ailixter\Gears\WatchedCall;

const BEFORE_CALL = '--before-call';
const AFTER_CALL  = '--after-call';

namespace Ailixter\Gears;

use Ailixter\Gears\WatchedCall\Helpers\Subject;
use Ailixter\Gears\WatchedCall\Helpers\SubjectInterface;
use const Ailixter\Gears\WatchedCall\BEFORE_CALL;
use const Ailixter\Gears\WatchedCall\AFTER_CALL;

/**
 * TODO Undocumented trait
 */
trait CallWatching
{
    /**
     * @var SubjectInterface[][]
     */
    private $watchingSubjects = [];

    protected function getWatchingSubject(string $eventName, string $methodName): ?SubjectInterface
    {
        return $this->watchingSubjects[$eventName][$methodName] ?? null;
    }

    protected function setWatchingSubject(string $eventName, string $methodName): SubjectInterface
    {
        return $this->watchingSubjects[$eventName][$methodName] = new Subject();
    }

    public function attachToWatch(string $eventName, string $methodName, callable $observer): self
    {
        if (!$subject = $this->getWatchingSubject($eventName, $methodName)) {
            $subject = $this->setWatchingSubject($eventName, $methodName);
        }
        $subject->attach($observer);
        return $this;
    }

    protected function getWatchedObject()
    {
        return $this;
    }

    public function __call($methodName, array $args)
    {
        $callable = [$this->getWatchedObject(), $methodName];
        if ($before = $this->getWatchingSubject(BEFORE_CALL, $methodName)) {
            $before($callable, $args);
        }
        $result = $callable(...$args);
        if ($after = $this->getWatchingSubject(AFTER_CALL, $methodName)) {
            $after($callable, $args, $result);
        }
        return $result;
    }
}
