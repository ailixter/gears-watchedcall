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

    public function getWatchingSubject(string $eventName, string $methodName): ?SubjectInterface
    {
        return $this->watchingSubjects[$eventName][$methodName] ?? null;
    }

    public function setWatchingSubject(string $eventName, string $methodName): SubjectInterface
    {
        return $this->watchingSubjects[$eventName][$methodName] = new Subject();
    }

    public function attachToWatch(string $eventName, string $methodName, callable $observer)
    {
        if (!$subject = $this->getWatchingSubject($eventName, $methodName)) {
            $subject = $this->setWatchingSubject($eventName, $methodName);
        }
        $subject->attach($observer);
    }

    public function getWatchedObject()
    {
        return $this;
    }

    public function __call($methodName, array $args)
    {
        $result = null;
        $doCall = true;
        if ($before = $this->getWatchingSubject(BEFORE_CALL, $methodName)) {
            $doCall = $before($this, $methodName, $args, $result) !== false;
        }
        if ($doCall) {
            $result = $this->getWatchedObject()->{$methodName}(...$args);
        }
        if ($after = $this->getWatchingSubject(AFTER_CALL, $methodName)) {
            $after($this, $methodName, $args, $result);
        }
        return $result;
    }
}

