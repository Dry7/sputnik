<?php

declare(strict_types=1);

namespace Sputnik\Models\Operations;

use Sputnik\Exceptions\InvalidOperation;
use Sputnik\Helpers\Validation;
use stdClass;

abstract class Operation
{
    const COOLING_SYSTEM_POWER_PCT = 'coolingSystemPowerPct';
    const MAIN_ENGINE_FUEL_PCT = 'mainEngineFuelPct';
    const MAIN_ENGINE_THRUST_PCT = 'mainEngineThrustPct';
    const ORIENTATION_AZIMUTH_ANGLE_DEG = 'orientationAzimuthAngleDeg';
    const ORIENTATION_ZENITH_ANGLE_DEG = 'orientationZenithAngleDeg';
    const RADIO_POWER_DBM = 'radioPowerDbm';
    const TEMPERATURE_INTERNAL_DEG = 'temperatureInternalDeg';
    const VESSEL_ALTITUDE_M = 'vesselAltitudeM';
    const VESSEL_SPEED_MPS = 'vesselSpeedMps';

    protected const VARIABLES = [
        self::COOLING_SYSTEM_POWER_PCT,
        self::MAIN_ENGINE_FUEL_PCT,
        self::MAIN_ENGINE_THRUST_PCT,
        self::ORIENTATION_AZIMUTH_ANGLE_DEG,
        self::ORIENTATION_ZENITH_ANGLE_DEG,
        self::RADIO_POWER_DBM,
        self::TEMPERATURE_INTERNAL_DEG,
        self::VESSEL_ALTITUDE_M,
        self::VESSEL_SPEED_MPS,
    ];

    protected const CRITICAL_DEFAULT = true;
    protected const MIN_VALUE = 0;
    protected const MAX_VALUE = 0;

    /** @var int */
    protected $id;

    /** @var int */
    protected $deltaT;

    /** @var string */
    protected $variable;

    /** @var int */
    protected $value;

    /** @var int */
    protected $timeout;

    /** @var bool */
    protected $critical;

    protected function __construct(int $id, int $deltaT, string $variable, int $value, int $timeout, bool $critical = self::CRITICAL_DEFAULT)
    {
        $this->setId($id);
        $this->setDeltaT($deltaT);
        $this->setVariable($variable);
        $this->setValue($value);
        $this->setTimeout($timeout);
        $this->setCritical($critical);
        $this->validate();
    }

    public static function createOperation(int $id, int $deltaT, string $variable, int $value, int $timeout, bool $critical = self::CRITICAL_DEFAULT): self
    {
        switch ($variable) {
            case self::COOLING_SYSTEM_POWER_PCT:
                return new CoolingSystemPowerPctOperation($id, $deltaT, $value, $timeout, $critical);

            case self::MAIN_ENGINE_FUEL_PCT:
                return new MainEngineFuelPctOperation($id, $deltaT, $value, $timeout, $critical);

            case self::MAIN_ENGINE_THRUST_PCT:
                return new MainEngineThrustPctOperation($id, $deltaT, $value, $timeout, $critical);

            case self::ORIENTATION_AZIMUTH_ANGLE_DEG:
                return new OrientationAzimuthAngleDegOperation($id, $deltaT, $value, $timeout, $critical);

            case self::ORIENTATION_ZENITH_ANGLE_DEG:
                return new OrientationZenithAngleDegOperation($id, $deltaT, $value, $timeout, $critical);

            case self::RADIO_POWER_DBM:
                return new RadioPowerDbmOperation($id, $deltaT, $value, $timeout, $critical);

            case self::TEMPERATURE_INTERNAL_DEG:
                return new TemperatureInternalDegOperation($id, $deltaT, $value, $timeout, $critical);

            case self::VESSEL_ALTITUDE_M:
                return new VesselAltitudeMOperation($id, $deltaT, $value, $timeout, $critical);

            case self::VESSEL_SPEED_MPS:
                return new VesselSpeedMpsOperation($id, $deltaT, $value, $timeout, $critical);

            default:
                throw InvalidOperation::variable(['method' => 'createOperation', 'variable' => $variable]);
        }
    }

    public static function createOperationFromJsonObject(stdClass $operation)
    {
        foreach (['id', 'deltaT', 'variable', 'value', 'timeout'] as $property) {
            if (!isset($operation->$property)) {
                throw InvalidOperation::propertyNotFound(['property' => $property, 'data' => $operation]);
            }
        }

        return self::createOperation(
            $operation->id,
            $operation->deltaT,
            $operation->variable,
            $operation->value,
            $operation->timeout,
            $operation->critical ?? self::CRITICAL_DEFAULT
        );
    }

    /**
     * @return int
     */
    public function getID(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function deltaT(): int
    {
        return $this->deltaT;
    }

    /**
     * @return string
     */
    public function variable(): string
    {
        return $this->variable;
    }

    /**
     * @return int
     */
    public function value(): int
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function timeout(): int
    {
        return $this->timeout;
    }

    /**
     * @return bool
     */
    public function critical(): bool
    {
        return $this->critical;
    }

    /**
     * @param int $startUp
     *
     * @return int
     */
    public function time(int $startUp): int
    {
        return $startUp + $this->deltaT;
    }

    public function __toString()
    {
        return json_encode([
            'class' => static::class,
            'id' => $this->id,
            'deltaT' => $this->deltaT,
            'variable' => $this->variable,
            'value' => $this->value,
            'timeout' => $this->timeout,
            'critical' => $this->critical,
        ]);
    }

    public function validate(): bool
    {
        if ($this->value >= static::MIN_VALUE && $this->value <= static::MAX_VALUE) {
            return true;
        } else {
            throw InvalidOperation::value(['operation' => $this]);
        }
    }

    /**
     * @param int $value
     *
     * @return Operation
     */
    public function setId(int $value): self
    {
        if ($value === 0 || !Validation::isUInt32($value)) {
            throw InvalidOperation::id(['operation' => $this, 'value' => $value]);
        }

        $this->id = $value;

        return $this;
    }

    /**
     * @param int $value
     *
     * @return Operation
     */
    public function setDeltaT(int $value): self
    {
        if (!Validation::isUInt32($value)) {
            throw InvalidOperation::deltaT(['operation' => $this, 'value' => $value]);
        }

        $this->deltaT = $value;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return Operation
     */
    public function setVariable(string $value): self
    {
        if (!in_array($value, self::VARIABLES)) {
            throw InvalidOperation::variable(['operation' => $this, 'value' => $value]);
        }

        $this->variable = $value;

        return $this;
    }

    /**
     * @param int $value
     *
     * @return Operation
     */
    public function setValue(int $value): self
    {
        if (!Validation::isUInt32($value)) {
            throw InvalidOperation::value(['operation' => $this, 'value' => $value]);
        }

        $this->value = $value;

        return $this;
    }

    /**
     * @param int $value
     *
     * @return Operation
     */
    public function setTimeout(int $value): self
    {
        if ($value === 0 || !Validation::isUInt32($value)) {
            throw InvalidOperation::timeout(['operation' => (string)$this, 'value' => $value]);
        }

        $this->timeout = $value;

        return $this;
    }

    /**
     * @param bool $value
     *
     * @return Operation
     */
    public function setCritical(bool $value): self
    {
        $this->critical = $value;

        return $this;
    }
}