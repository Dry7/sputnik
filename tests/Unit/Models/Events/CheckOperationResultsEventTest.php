<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Events;

use Mockery\MockInterface;
use Sputnik\Exceptions\InvalidCheck;
use Sputnik\Exceptions\RequestException;
use Sputnik\Models\Events\CheckOperationResultsEvent;
use Sputnik\Models\Events\Event;
use Sputnik\Models\Events\StartOperationEvent;
use Sputnik\Models\Operations\Operation;
use Sputnik\Services\ExchangeService;
use Tests\TestCase;
use stdClass;

class CheckOperationResultsEventTest extends TestCase
{
    public function t2estExecute(): void
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
        $stub = $this->spy(CheckOperationResultsEvent::class)
            ->makePartial()
            ->setTime(1555016400)
            ->setOperation($operation);

        $this->mock(
            ExchangeService::class,
            static fn ($mock) => $mock->shouldReceive('get')
                ->with([$operation->variable()])
                ->andReturn($data)
                ->once()
        );

        // act
        $stub->execute();

        // assert
        $stub->shouldHaveReceived('validateResult')->with($data)->once();
    }

    public function testExecuteException(): void
    {
        // assert
        self::expectException(InvalidCheck::class);

        // arrange
        $event = self::createEvent(Event::TYPE_CHECK_OPERATION_RESULTS);

        $this->mock(
            ExchangeService::class,
            static fn ($mock) => $mock->shouldReceive('get')
                ->with([$event->getOperation()->variable()])
                ->andThrow(RequestException::timeout())
                ->once()
        );

        // act
        $event->execute();
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
                    Operation::MAIN_ENGINE_FUEL_PCT => (object)['set' => 10, 'value' => 0]
                ],
            ],
        ];
    }

    /**
     * @dataProvider validateResultDataProvider
     *
     * @param stdClass $data
     */
    public function testValidateResult(stdClass $data): void
    {
        // arrange
        $operation = Operation::createOperation(
            1,
            0,
            Operation::MAIN_ENGINE_FUEL_PCT,
            0,
            1
        );
        $event = new CheckOperationResultsEvent(1555016400, $operation);

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
                'Sputnik\Exceptions\InvalidCheck',
            ],
            'plain value' => [
                (object)[
                    Operation::MAIN_ENGINE_FUEL_PCT => 5,
                ],
                'Sputnik\Exceptions\InvalidCheck',
            ],
            'array value' => [
                (object)[
                    Operation::MAIN_ENGINE_FUEL_PCT => ['set' => 5, 'value' => 5],
                ],
                'Sputnik\Exceptions\InvalidCheck',
            ],
            'wrong type' => [
                (object)[
                    Operation::ORIENTATION_AZIMUTH_ANGLE_DEG => (object)['set' => 5, 'value' => 5],
                ],
                'Sputnik\Exceptions\InvalidCheck',
            ],
            'wrong set' => [
                (object)[
                    Operation::MAIN_ENGINE_FUEL_PCT => (object)['set' => 5, 'value' => 5],
                ],
                'Sputnik\Exceptions\InvalidCheck',
            ],
            'incorrect value' => [
                (object)[
                    Operation::MAIN_ENGINE_FUEL_PCT => (object)['set' => 0, 'value' => 5],
                ],
                'Sputnik\Exceptions\InvalidCheck',
            ],
            'wrong data type' => [
                (object)[
                    Operation::MAIN_ENGINE_FUEL_PCT => (object)['set' => 0, 'value' => 'test'],
                ],
                'Sputnik\Exceptions\InvalidCheck',
            ],
        ];
    }

    /**
     * @dataProvider validateResultInvalidDataProvider
     *
     * @param stdClass $data
     * @param string $exception
     */
    public function testValidateResultInvalidCritical(stdClass $data, string $exception): void
    {
        // arrange
        $operation = Operation::createOperation(
            1,
            0,
            Operation::MAIN_ENGINE_FUEL_PCT,
            0,
            1
        );
        $event = new CheckOperationResultsEvent(1555016400, $operation);

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
    public function testValidateResultInvalidNotCritical(stdClass $data, string $exception): void
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
        $event = new CheckOperationResultsEvent(1555016400, $operation);

        // act
        self::assertFalse($event->validateResult($data));
    }
}
