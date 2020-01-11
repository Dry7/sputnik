<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Events;

use Mockery\MockInterface;
use Sputnik\Models\Events\StartOperationEvent;
use Sputnik\Models\Operations\Operation;
use Sputnik\Services\ExchangeService;
use Tests\TestCase;
use stdClass;

class StartOperationEventTest extends TestCase
{
    public function testExecute()
    {
        // arrange
        $operation = self::createOperation();
        $data = (object)[
            $operation->variable() => (object)[
                'set' => $operation->value(),
                'value' => $operation->value(),
            ],
        ];

        /** @var MockInterface|StartOperationEvent $stub */
        $stub = $this->spy(StartOperationEvent::class)
            ->makePartial()
            ->setTime(1555016400)
            ->setOperation($operation);

        $this->mock(ExchangeService::class, static function ($mock) use ($operation, $data) {
            $mock->shouldReceive('patch')
                ->with([$operation->variable() => $operation->value()])
                ->andReturn($data)
                ->once();
        });

        // act
        $stub->execute();

        // assert
        $stub->shouldHaveReceived('validateResult')->with($data)->once();
    }

    public static function validateResultDataProvider()
    {
        return [
            [
                (object)[
                    Operation::MAIN_ENGINE_FUEL_PCT => (object)['set' => 0, 'value' => 0]
                ],
            ],
            [
                (object)[
                    Operation::MAIN_ENGINE_FUEL_PCT => (object)['set' => 0, 'value' => 10]
                ],
            ],
        ];
    }

    /**
     * @dataProvider validateResultDataProvider
     *
     * @param stdClass $data
     */
    public function testValidateResult(stdClass $data)
    {
        // arrange
        $operation = Operation::createOperation(
            1,
            0,
            Operation::MAIN_ENGINE_FUEL_PCT,
            0,
            1
        );
        $event = new StartOperationEvent(1555016400, $operation);

        // act
        $result = $event->validateResult($data);

        // assert
        self::assertTrue($result);
    }

    public static function validateResultInvalidDataProvider()
    {
        return [
            'null' => [
                (object)[],
                'Sputnik\Exceptions\EventException',
            ],
            'plain value' => [
                (object)[
                    Operation::MAIN_ENGINE_FUEL_PCT => 5,
                ],
                'Sputnik\Exceptions\EventException',
            ],
            'array value' => [
                (object)[
                    Operation::MAIN_ENGINE_FUEL_PCT => ['set' => 5, 'value' => 5],
                ],
                'Sputnik\Exceptions\EventException',
            ],
            'wrong type' => [
                (object)[
                    Operation::ORIENTATION_AZIMUTH_ANGLE_DEG => (object)['set' => 5, 'value' => 5],
                ],
                'Sputnik\Exceptions\EventException',
            ],
            'wrong set' => [
                (object)[
                    Operation::MAIN_ENGINE_FUEL_PCT => (object)['set' => 5, 'value' => 5],
                ],
                'Sputnik\Exceptions\EventException',
            ],
            'incorrect value' => [
                (object)[
                    Operation::MAIN_ENGINE_FUEL_PCT => (object)['set' => 0, 'value' => 200],
                ],
                'Sputnik\Exceptions\InvalidOperation',
            ],
        ];
    }

    /**
     * @dataProvider validateResultInvalidDataProvider
     *
     * @param stdClass $data
     * @param string $exception
     */
    public function testValidateResultInvalidCritical(stdClass $data, string $exception)
    {
        // arrange
        $operation = Operation::createOperation(
            1,
            0,
            Operation::MAIN_ENGINE_FUEL_PCT,
            0,
            1
        );
        $event = new StartOperationEvent(1555016400, $operation);

        // assert
        self::expectException($exception);

        // act
        $event->validateResult($data);
    }

    /**
     * @dataProvider validateResultInvalidDataProvider
     *
     * @param stdClass $data
     * @param string $exception
     */
    public function testValidateResultInvalidNotCritical(stdClass $data, string $exception)
    {
        // arrange
        $operation = Operation::createOperation(
            1,
            0,
            Operation::MAIN_ENGINE_FUEL_PCT,
            0,
            1,
            false
        );
        $event = new StartOperationEvent(1555016400, $operation);

        // act
        $this->assertFalse($event->validateResult($data));
    }
}
