<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Events;

use Sputnik\Exceptions\InvalidEvent;
use Sputnik\Models\Events\Event;
use Sputnik\Models\Operations\Operation;
use Tests\TestCase;
use TypeError;
use stdClass;

class EventTest extends TestCase
{
    public static function createEventDataProvider()
    {
        $operation = self::createOperation();
        return [
            [
                1555016400,
                Event::TYPE_START_OPERATION,
                $operation,
            ],
            [
                1555016400,
                Event::TYPE_CHECK_OPERATION_RESULTS,
                $operation,
            ],
        ];
    }

    /**
     * @dataProvider createEventDataProvider
     *
     * @param int $time
     * @param string $type
     * @param Operation $operation
     */
    public function testCreateEvent(int $time, string $type, Operation $operation): void
    {
        // act
        $result = Event::createEvent($time, $type, $operation);

        // assert
        self::assertInstanceOf(Event::class, $result);
        self::assertEquals($time, $result->getTime());
        self::assertEquals($type, $result->getType());
        self::assertEquals($operation, $result->getOperation());
    }

    public function testCreateEventInvalidType(): void
    {
        // assert
        self::expectException(InvalidEvent::class);
        self::expectExceptionMessage('Invalid event: type');

        // act
        Event::createEvent(1542014400, 'test', self::createOperation());
    }

    public function testSetTime(): void
    {
        // act
        $result = self::createEvent()->setTime(1542014433);

        // assert
        self::assertEquals(1542014433, $result->getTime());
    }

    public static function setTimeInvalidDataProvider()
    {
        return [
            [-100, InvalidEvent::class],
            ["test", TypeError::class],
        ];
    }

    /**
     * @dataProvider setTimeInvalidDataProvider
     *
     * @param int|string $time
     * @param string $exception
     */
    public function testSetTimeInvalid($time, string $exception): void
    {
        // assert
        self::expectException($exception);

        // act
        self::createEvent()->setTime($time);
    }

    public function testSetType(): void
    {
        // act
        $result = self::createEvent()
            ->setType(Event::TYPE_CHECK_OPERATION_RESULTS);

        // assert
        self::assertEquals(Event::TYPE_CHECK_OPERATION_RESULTS, $result->getType());
    }

    public static function setTypeInvalidDataProvider()
    {
        return [
            ['test', InvalidEvent::class],
            [new stdClass(), TypeError::class],
        ];
    }

    /**
     * @dataProvider setTypeInvalidDataProvider
     *
     * @param int|string $type
     * @param string $exception
     */
    public function testSetTypeInvalid($type, string $exception): void
    {
        // assert
        self::expectException($exception);

        // act
        self::createEvent()->setType($type);
    }

    public function testSetOperation(): void
    {
        // arrange
        $operation = self::createOperation();

        // act
        $result = self::createEvent()->setOperation($operation);

        // assert
        self::assertEquals($operation, $result->getOperation());
    }

    public static function setOperationInvalidDataProvider()
    {
        return [
            [10, TypeError::class],
            ['test', TypeError::class],
            [self::createEvent(), TypeError::class],
        ];
    }

    /**
     * @dataProvider setOperationInvalidDataProvider
     *
     * @param mixed $operation
     * @param string $exception
     */
    public function testSetOperationInvalid($operation, string $exception): void
    {
        // assert
        self::expectException($exception);

        // act
        self::createEvent()->setOperation($operation);
    }

    public function testToString(): void
    {
        // arrange
        $operation = Operation::createOperation(
            1,
            0,
            Operation::ORIENTATION_AZIMUTH_ANGLE_DEG,
            0,
            1
        );

        //act
        $event = Event::createEvent(
            1555016400,
            Event::TYPE_START_OPERATION,
            $operation
        );

        // assert
        self::assertEquals(
            '{"class":"Sputnik\\\\Models\\\\Events\\\\StartOperationEvent","type":"start_operation","operation":"{\"class\":\"Sputnik\\\\\\\\Models\\\\\\\\Operations\\\\\\\\OrientationAzimuthAngleDegOperation\",\"id\":1,\"deltaT\":0,\"variable\":\"orientationAzimuthAngleDeg\",\"value\":0,\"timeout\":1,\"critical\":true}"}',
            (string)$event
        );
    }
}
