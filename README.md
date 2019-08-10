# gears-watchedcall
Hooks before/after any method call

## Before a call

```php
    use Ailixter\Gears\CallWatching;
    use const Ailixter\Gears\WatchedCall\BEFORE_CALL;
    use const Ailixter\Gears\WatchedCall\AFTER_CALL;

    class Example
    {
        use CallWatching;
        /**
         * @access public
         * via private modifier
         */
        private function watchedMethod($arg1, $arg2)
        {
            return $arg1 + $arg2;
        }
    }

    $example = new Example;
    $example->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable $callable, array $args) {
        // $callable is guaranteed to be in form [object, method]
        [$object, $method] = $callable;
        // inspect $args
        // ...
        // do something else
    });
    $result = $example->watchedMethod(2, 3); // 5
```

### Loud Access Check
```php
    $example->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable $callable) {
        if (!User::canAccess($callable)) {
            throw new UserAccessException;
        }
    });
    $result = $example->watchedMethod(2, 3); // exception
```

### Quiet Access Check
```php
    $example->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable &$callable) {
        if (!User::canAccess($callable)) {
            // just return something acceptable
            $callable = function () { return null; }
        }
    });
    $result = $example->watchedMethod(2, 3); // null
```

### Fallback For Unknown Methods
```php
    $example->attachToWatch(BEFORE_CALL, 'unknownMethod', function (callable &$callable) {
        // just return something acceptable
        $callable = function () { return null; }
    });
    $result = $example->unknownMethod(2, 3); // null
```

### Tampering Routine
```php
    $example->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable &$callable) {
        $callable = 'min';
    });
    $result = $example->watchedMethod(2, 3); // 2
```

### Tampering Arguments
```php
    $example->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable $callable, array &$args) {
        $args = array_map(function (int $arg) { return $arg * $arg; }, $args);
    });
    $result = $example->watchedMethod(2, 3); // 13
```

### Classic Decoration
```php
    $done = false;
    $example->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable &$callable) use (&$done) {
        if ($done) return;
        $done = true;
        $callable = function (...$args) use ($callable) {
            return $callable(...array_map(function (int $arg) { return $arg * $arg; }, $args)) + 1;
        };
    });
    $result = $example->watchedMethod(2, 3); // 14
```

## After a call

### Result Substitution
```php
    $example->attachToWatch(AFTER_CALL, 'watchedMethod', function (callable $callable, array $args, &$result) {
        $result *= 2;
    });
    $result = $example->watchedMethod(2, 3); // 10
```

### Loud Result Check
```php
    $example->attachToWatch(AFTER_CALL, 'watchedMethod', function (callable $callable, array $args, $result) {
        if (array_sum($args) != $result) {
            throw new CalculationError;
        }
    });
    $result = $example->watchedMethod(2, 3); // 10
```

### Quiet Result Check
```php
    $example->attachToWatch(AFTER_CALL, 'watchedMethod', function (callable $callable, array $args, &$result) {
        if (array_sum($args) != $result) {
            $result = null;
        }
    });
    $result = $example->watchedMethod(2, 3); // 10
```

## Decoration
```php
    $example
    ->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable $callable, array &$args) {
        $args = array_map(function (int $arg) { return $arg * $arg; }, $args);
    })
    ->attachToWatch(AFTER_CALL, 'watchedMethod', function (callable $callable, array $args, &$result) {
        $result += 1
    });
    $result = $example->watchedMethod(2, 3); // 14
```

## Watching via Delegation
```php
    class Delegate
    {
        public function watchedMethod($arg1, $arg2)
        {
            return $arg1 + $arg2;
        }
    }

    class Example
    {
        use CallWatching;
        private $delegate;
        public function __construct(Delegate $delegate)
        {
            $this->delegate = $delegate;
        }
        /**
         * note overriding
         */
        protected function getWatchedObject()
        {
            return $this->delegate;
        }
    }
```