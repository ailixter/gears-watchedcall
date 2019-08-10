<?php

use PHPUnit\Framework\TestCase;
use Ailixter\Gears\CallWatching;
use const Ailixter\Gears\WatchedCall\BEFORE_CALL;
use const Ailixter\Gears\WatchedCall\AFTER_CALL;

class MainTest extends TestCase
{
    private $test;

    protected function setUp(): void
    {
        $this->test = new class {
            use CallWatching;

            public function publicMethod(string $param)
            {
                return $param . '.';
            }
            /** @access public */
            private function notWatchedMethod(string $param)
            {
                return $param . '!';
            }
            /** @access public */
            private function watchedMethod(string $param)
            {
                return $param . '?';
            }
            /** @access public */
            private function watchedFail(string $param)
            {
                throw new \Exception($param);
            }
        };
    }

    /*
     * @test
     */
    public function testPublic()
    {
        $result = $this->test->publicMethod('OK');
        $this->assertStringEndsWith('.', $result);
    }

    public function testNotWatched()
    {
        $result = $this->test->notWatchedMethod('OK');
        $this->assertStringEndsWith('!', $result);
    }

    /** @test */
    public function testWatched()
    {
        $data = new \stdClass;
        $this->test->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable $callable, array $args) use ($data) {
            [$object, $method] = $callable;
            $data->object = $object;
            $data->method = $method;
            $data->args   = $args;
        });
        $this->test->watchedMethod('OK');
        $this->assertNotEquals(new \stdClass, $data);
        $this->assertSame($this->test, $data->object);
        $this->assertEquals('watchedMethod', $data->method);
        $this->assertContains('OK', $data->args);
    }

    public function testWatchedRedirect()
    {
        $this->test->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable &$callable, array &$args) {
            $callable = 'strtolower';
            $args[0] = 'GOTCHA';
        });
        $result = $this->test->watchedMethod('OK');
        $this->assertEquals('gotcha', $result);
    }

    public function testDisallowed()
    {
        $this->test->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable $callable) {
            [, $method] = $callable;
            throw new \RuntimeException("Disallowed " . $method);
        });
        try {
            $this->test->watchedMethod('OK');
            $called = true;
        } catch (\RuntimeException $e) {
            $called = false;
        }
        $this->assertTrue(isset($called));
        $this->assertFalse($called);
    }

    public function testWatchedResult()
    {
        $data = new \stdClass;
        $this->test->attachToWatch(AFTER_CALL, 'watchedMethod', function (callable $callable, array $args, $result) use ($data) {
            [$data->object, $data->method] = $callable;
            $data->args   = $args;
            $data->result = $result;
        });
        $result = $this->test->watchedMethod('OK');
        $this->assertNotEquals(new \stdClass, $data);
        $this->assertSame($this->test, $data->object);
        $this->assertEquals('watchedMethod', $data->method);
        $this->assertContains('OK', $data->args);
        $this->assertEquals($result, $data->result);
        $this->assertEquals('OK?', $data->result);
    }

    public function testWatchedResultChange()
    {
        $this->test->attachToWatch(AFTER_CALL, 'watchedMethod', function (callable $callable, array $args, &$result) {
            $result .= ' ' . $callable[1];
        });
        $result = $this->test->watchedMethod('OK');
        $this->assertEquals('OK? watchedMethod', $result);
    }

    public function testInterception()
    {
        $this->test->attachToWatch(BEFORE_CALL, 'watchedFail', function (callable &$callable) {
            [, $method] = $callable;
            $callable = function () use ($method) { return "Intercepted " . $method; };
        });
        $this->assertEquals('Intercepted watchedFail', $this->test->watchedFail('Disaster'));
    }

    public function testInterception2()
    {
        $this->test->attachToWatch(BEFORE_CALL, 'unknownMethod', function (callable &$callable) {
            [, $method] = $callable;
            $callable = function () use ($method) { return "Intercepted " . $method; };
        });
        $this->assertEquals('Intercepted unknownMethod', $this->test->unknownMethod('OK'));
    }

    public function testDecorate()
    {
        $this->test->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable &$callable, array &$args) {
            $args[0] .= ' forged arg';
        });
        $this->test->attachToWatch(AFTER_CALL, 'watchedMethod', function (callable $callable, array $args, &$result) {
            $result .= ' returns forged result';
        });
        $result = $this->test->watchedMethod('OK');
        $this->assertEquals('OK forged arg? returns forged result', $result);
    }

    public function testDecorateClassically()
    {
        $done = false;
        $this->test->attachToWatch(BEFORE_CALL, 'watchedMethod', function (callable &$callable) use (&$done) {
            if ($done) return;
            $done = true;
            $callable = function (/*...$args*/) use ($callable) {
                return $callable('forged arg') . ' returns forged result';
            };
        });
        $this->assertEquals('forged arg? returns forged result', $this->test->watchedMethod('OK'));
    }
}