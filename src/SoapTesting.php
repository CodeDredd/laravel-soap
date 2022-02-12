<?php

namespace CodeDredd\Soap;

use CodeDredd\Soap\Client\Request;
use PHPUnit\Framework\Assert as PHPUnit;

class SoapTesting
{
    /**
     * @var SoapFactory|null
     */
    protected $factory;

    /**
     * Create a new Soap Testing instance.
     *
     * @param  \CodeDredd\Soap\SoapFactory|null  $factory
     * @return void
     */
    public function __construct(SoapFactory $factory = null)
    {
        $this->factory = $factory;
    }

    /**
     * Assert that a request / response pair was not recorded matching a given truth test.
     *
     * @param  callable  $callback
     * @return void
     */
    public function assertNotSent($callback)
    {
        PHPUnit::assertFalse(
            $this->factory->recorded($callback)->count() > 0,
            'Unexpected request was recorded.'
        );
    }

    /**
     * Assert that no request / response pair was recorded.
     *
     * @return void
     */
    public function assertNothingSent()
    {
        PHPUnit::assertEmpty(
            $this->factory->getRecorded(),
            'Requests were recorded.'
        );
    }

    /**
     * Assert that every created response sequence is empty.
     *
     * @return void
     */
    public function assertSequencesAreEmpty()
    {
        foreach ($this->factory->getResponseSequences() as $responseSequence) {
            PHPUnit::assertTrue(
                $responseSequence->isEmpty(),
                'Not all response sequences are empty.'
            );
        }
    }

    /**
     * Assert that a given soap action is called with optional arguments.
     *
     * @param  string  $action
     * @return void
     */
    public function assertActionCalled(string $action)
    {
        $this->assertSent(function (Request $request) use ($action) {
            return $request->action() === $action;
        });
    }

    /**
     * Assert that a request / response pair was recorded matching a given truth test.
     *
     * @param  callable  $callback
     * @return void
     */
    public function assertSent($callback)
    {
        PHPUnit::assertTrue(
            $this->factory->recorded($callback)->count() > 0,
            'An expected request was not recorded.'
        );
    }

    /**
     * Assert how many requests have been recorded.
     */
    public function assertSentCount(int $count): void
    {
        PHPUnit::assertCount($count, $this->factory->getRecorded());
    }

    /**
     * Assert that the given request was sent in the given order.
     *
     * @param  array  $callbacks
     * @return void
     */
    public function assertSentInOrder($callbacks)
    {
        $this->assertSentCount(count($callbacks));

        foreach ($callbacks as $index => $url) {
            $callback = is_callable($url) ? $url : function ($request) use ($url) {
                return $request->url() == $url;
            };

            PHPUnit::assertTrue($callback(
                $this->factory->getRecorded()[$index][0],
                $this->factory->getRecorded()[$index][1]
            ), 'An expected request (#'.($index + 1).') was not recorded.');
        }
    }
}
