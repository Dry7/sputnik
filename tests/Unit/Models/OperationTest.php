<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Sputnik\Exceptions\InvalidOperation;
use Sputnik\Models\Operations\CoolingSystemPowerPctOperation;
use Sputnik\Models\Operations\MainEngineFuelPctOperation;
use Sputnik\Models\Operations\MainEngineThrustPctOperation;
use Sputnik\Models\Operations\Operation;
use Sputnik\Models\Operations\OrientationAzimuthAngleDegOperation;
use Sputnik\Models\Operations\OrientationZenithAngleDegOperation;
use Sputnik\Models\Operations\RadioPowerDbmOperation;
use Sputnik\Models\Operations\TemperatureInternalDegOperation;
use Sputnik\Models\Operations\VesselAltitudeMOperation;
use Sputnik\Models\Operations\VesselSpeedMpsOperation;
use Tests\TestCase;
use TypeError;

class OperationTest extends TestCase
{
    public static function createOperationDataProvider()
    {
        return [
            [Operation::COOLING_SYSTEM_POWER_PCT, CoolingSystemPowerPctOperation::class],
            [Operation::MAIN_ENGINE_FUEL_PCT, MainEngineFuelPctOperation::class],
            [Operation::MAIN_ENGINE_THRUST_PCT, MainEngineThrustPctOperation::class],
            [Operation::ORIENTATION_AZIMUTH_ANGLE_DEG, OrientationAzimuthAngleDegOperation::class],
            [Operation::ORIENTATION_ZENITH_ANGLE_DEG, OrientationZenithAngleDegOperation::class],
            [Operation::RADIO_POWER_DBM, RadioPowerDbmOperation::class],
            [Operation::TEMPERATURE_INTERNAL_DEG, TemperatureInternalDegOperation::class],
            [Operation::VESSEL_ALTITUDE_M, VesselAltitudeMOperation::class],
            [Operation::VESSEL_SPEED_MPS, VesselSpeedMpsOperation::class],
        ];
    }

    /**
     * @dataProvider createOperationDataProvider
     *
     * @param string $type
     * @param string $class
     */
    public function testCreateOperation(string $type, string $class)
    {
        // act
        $result = Operation::createOperation(1, 0, $type, 20, 1);

        // assert
        self::assertInstanceOf($class, $result);
    }

    public function testCreateOperationInvalidType()
    {
        // assert
        self::expectException(InvalidOperation::class);
        self::expectExceptionMessage('Invalid operation: variable');

        // act
        Operation::createOperation(1, 0, 'test', 20, 1);
    }

    public function testCreateOperationFromJsonObject()
    {
        // arrange
        $operation = $this->createOperation();
        $data = (object)[
            'id' => $operation->getID(),
            'deltaT' => $operation->deltaT(),
            'variable' => $operation->variable(),
            'value' => $operation->value(),
            'timeout' => $operation->timeout(),
            'critical' => $operation->critical()
        ];

        // act
        $result = Operation::createOperationFromJsonObject($data);

        // assert
        self::assertSame((string)$operation, (string)$result);
    }

    public static function createOperationFromJsonObjectInvalidDataProvider()
    {
        return [
            [
                ['deltaT', 'variable', 'value', 'timeout'],
            ],
            [
                ['id', 'variable', 'value', 'timeout'],
            ],
            [
                ['id', 'deltaT', 'value', 'timeout'],
            ],
            [
                ['id', 'deltaT', 'variable', 'timeout'],
            ],
            [
                ['id', 'deltaT', 'variable', 'value'],
            ],
        ];
    }

    /**
     * @dataProvider createOperationFromJsonObjectInvalidDataProvider
     *
     * @param array $data
     */
    public function testCreateOperationFromJsonObjectInvalid(array $data)
    {
        // assert
        self::expectException(InvalidOperation::class);

        Operation::createOperationFromJsonObject((object)$data);
    }

    public function testGetID()
    {
        // arrange
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        );

        // act
        $result = $operation->getID();

        // assert
        self::assertSame(1, $result);
    }

    public function testGetDeltaT()
    {
        // arrange
        /** @var Operation $operation */
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        );

        // act
        $result = $operation->deltaT();

        // assert
        self::assertSame(0, $result);
    }

    public function testGetVariable()
    {
        // arrange
        /** @var Operation $operation */
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        );

        // act
        $result = $operation->variable();

        // assert
        self::assertSame(Operation::RADIO_POWER_DBM, $result);
    }

    public function testGetValue()
    {
        // arrange
        /** @var Operation $operation */
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        );

        // act
        $result = $operation->value();

        // assert
        self::assertSame(20, $result);
    }

    public function testGetTimeout()
    {
        // arrange
        /** @var Operation $operation */
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        );

        // act
        $result = $operation->timeout();

        // assert
        self::assertSame(1, $result);
    }

    public function testGetCritical()
    {
        // arrange
        /** @var Operation $operation */
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1,
            false
        );

        // act
        $result = $operation->critical();

        // assert
        self::assertSame(false, $result);
    }

    public function testSetID()
    {
        // arrange
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        )->setId(2);

        // act
        $result = $operation->getID();

        // assert
        self::assertSame(2, $result);
    }

    public static function setIDInvalidDataProvider()
    {
        return [
            [-100, InvalidOperation::class],
            [0, InvalidOperation::class],
            [4294967296, InvalidOperation::class],
            ['test', TypeError::class],
        ];
    }

    /**
     * @dataProvider setIDInvalidDataProvider
     *
     * @param $value
     * @param string $exception
     */
    public function testSetIDZero($value, string $exception)
    {
        self::expectException($exception);

        // act
        Operation::createOperation(
            $value,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        );
    }

    public function testSetDeltaT()
    {
        // arrange
        /** @var Operation $operation */
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        )->setDeltaT(2);

        // act
        $result = $operation->deltaT();

        // assert
        self::assertSame(2, $result);
    }

    public static function setDeltaTInvalidDataProvider()
    {
        return [
            [-100, InvalidOperation::class],
            [4294967296, InvalidOperation::class],
            ['test', TypeError::class],
        ];
    }

    /**
     * @dataProvider setDeltaTInvalidDataProvider
     *
     * @param $value
     * @param string $exception
     */
    public function testSetDeltaTZero($value, string $exception)
    {
        self::expectException($exception);

        // act
        Operation::createOperation(
            1,
            $value,
            Operation::RADIO_POWER_DBM,
            20,
            1
        );
    }

    public function testSetVariable()
    {
        // arrange
        /** @var Operation $operation */
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        )->setVariable(Operation::VESSEL_SPEED_MPS);

        // act
        $result = $operation->variable();

        // assert
        self::assertSame(Operation::VESSEL_SPEED_MPS, $result);
    }

    public function testSetVariableZero()
    {
        self::expectException(InvalidOperation::class);

        // act
        Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        )->setVariable('test');
    }

    public function tesSetValue()
    {
        // arrange
        /** @var Operation $operation */
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        )->setValue(21);

        // act
        $result = $operation->value();

        // assert
        self::assertSame(21, $result);
    }

    public static function setValueInvalidDataProvider()
    {
        return [
            [-100, InvalidOperation::class],
            [4294967296, InvalidOperation::class],
            ['test', TypeError::class],
        ];
    }

    /**
     * @dataProvider setValueInvalidDataProvider
     *
     * @param $value
     * @param string $exception
     */
    public function testSetValueTZero($value, string $exception)
    {
        self::expectException($exception);

        // act
        Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            $value,
            1
        );
    }

    public function testSetTimeout()
    {
        // arrange
        /** @var Operation $operation */
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1
        )->setTimeout(3);

        // act
        $result = $operation->timeout();

        // assert
        self::assertSame(3, $result);
    }

    public static function setTimeoutInvalidDataProvider()
    {
        return [
            [-100, InvalidOperation::class],
            [0, InvalidOperation::class],
            [4294967296, InvalidOperation::class],
            ['test', TypeError::class],
        ];
    }

    /**
     * @dataProvider setTimeoutInvalidDataProvider
     *
     * @param $value
     * @param string $exception
     */
    public function testSetTimeoutZero($value, string $exception)
    {
        self::expectException($exception);

        // act
        Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            $value
        );
    }

    public function testSetCritical()
    {
        // arrange
        /** @var Operation $operation */
        $operation = Operation::createOperation(
            1,
            0,
            Operation::RADIO_POWER_DBM,
            20,
            1,
            false
        )->setCritical(true);

        // act
        $result = $operation->critical();

        // assert
        self::assertSame(true, $result);
    }
}
