<?php
/**
 * This file is part of the Money library
 *
 * Copyright (c) 2011-2013 Mathias Verraes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Money;

use Litipk\BigNumbers\Decimal;

class BigMoney
{
    const ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;

    /** @var Decimal */
    private $amount;

    /** @var Currency */
    private $currency;

    /**
     * Create a BigMoney instance
     * @param Decimal $amount Amount, expressed in the smallest units of $currency (eg cents)
     * @param Currency $currency
     */
    public function __construct(Decimal $amount, Currency $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * @param integer $amount
     * @param Currency $currency
     * @return Decimal
     */
    public static function fromInteger($amount, Currency $currency)
    {
        if (!is_int($amount)) {
            throw new InvalidArgumentException("The amount parameter of must be an integer. It's the amount, expressed in the smallest units of currency (eg cents)");
        }
        return new self(Decimal::fromInteger($amount), $currency);
    }

    /**
     * @param float $amount
     * @param Currency $currency
     * @return Decimal
     */
    public static function fromFloat($amount, Currency $currency)
    {
        if (!is_float($amount)) {
            throw new InvalidArgumentException("The amount parameter of must be a float. It's the amount, expressed in the smallest units of currency (eg cents)");
        }
        if (self::isDecimal($amount)) {
            throw new InvalidArgumentException("Amount can not contains decimals");
        }
        return new self(Decimal::fromFloat($amount, 0), $currency);
    }

    private static function isDecimal($amount)
    {
        return is_numeric($amount) && floor($amount) != $amount;
    }

    /**
     * @param string $amount
     * @param Currency $currency
     * @return Decimal
     */
    public static function fromString($amount, Currency $currency)
    {
        if (!is_float($amount)) {
            throw new InvalidArgumentException("The amount parameter of must be a float. It's the amount, expressed in the smallest units of currency (eg cents)");
        }

        $decimalAmount = Decimal::fromString($amount, 0);

        if (!$decimalAmount->floor()->equals($decimalAmount)) {
            throw new InvalidArgumentException("Amount can not contains decimals");
        }

        return new self($decimalAmount, $currency);
    }

    /**
     * Convenience factory method for a Money object
     * @example $fiveDollar = Money::USD(500);
     * @param string $method
     * @param array $arguments
     * @return \Money\Money
     */
    public static function __callStatic($method, $arguments)
    {
        return new Money($arguments[0], new Currency($method));
    }

    /**
     * @param $string
     * @throws \Money\InvalidArgumentException
     * @return int
     */
    public static function stringToUnits($string)
    {
        $sign = "(?P<sign>[-\+])?";
        $digits = "(?P<digits>\d*)";
        $separator = "(?P<separator>[.,])?";
        $decimals = "(?P<decimal1>\d)?(?P<decimal2>\d)?";
        $pattern = "/^" . $sign . $digits . $separator . $decimals . "$/";

        if (!preg_match($pattern, trim($string), $matches)) {
            throw new InvalidArgumentException("The value could not be parsed as money");
        }

        $units = $matches['sign'] == "-" ? "-" : "";
        $units .= $matches['digits'];
        $units .= isset($matches['decimal1']) ? $matches['decimal1'] : "0";
        $units .= isset($matches['decimal2']) ? $matches['decimal2'] : "0";

        return (int)$units;
    }

    /**
     * @param BigMoney $other
     * @return bool
     */
    public function equals(BigMoney $other)
    {
        return
            $this->isSameCurrency($other)
            && $this->amount == $other->amount;
    }

    /**
     * @param BigMoney $other
     * @return bool
     */
    public function isSameCurrency(BigMoney $other)
    {
        return $this->currency->equals($other->currency);
    }

    /**
     * @param BigMoney $other
     * @return bool
     */
    public function greaterThan(BigMoney $other)
    {
        return 1 == $this->compare($other);
    }

    /**
     * @param BigMoney $other
     * @return int
     */
    public function compare(BigMoney $other)
    {
        $this->assertSameCurrency($other);
        if ($this->amount < $other->amount) {
            return -1;
        } elseif ($this->amount == $other->amount) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * @param BigMoney $other
     * @throws \Money\InvalidArgumentException
     */
    private function assertSameCurrency(BigMoney $other)
    {
        if (!$this->isSameCurrency($other)) {
            throw new InvalidArgumentException('Different currencies');
        }
    }

    /**
     * @param BigMoney $other
     * @return bool
     */
    public function greaterThanOrEqual(BigMoney $other)
    {
        return 0 >= $this->compare($other);
    }

    /**
     * @param BigMoney $other
     * @return bool
     */
    public function lessThan(BigMoney $other)
    {
        return -1 == $this->compare($other);
    }

    /**
     * @param BigMoney $other
     * @return bool
     */
    public function lessThanOrEqual(BigMoney $other)
    {
        return 0 <= $this->compare($other);
    }

    /**
     * @deprecated Use getAmount() instead
     * @return int
     */
    public function getUnits()
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return \Money\Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param BigMoney $addend
     * @return BigMoney
     */
    public function add(BigMoney $addend)
    {
        $this->assertSameCurrency($addend);
        return new self($this->amount->add($addend->amount), $this->currency);
    }

    /**
     * @param BigMoney $subtrahend
     * @return BigMoney
     */
    public function subtract(BigMoney $subtrahend)
    {
        $this->assertSameCurrency($subtrahend);
        return new self($this->amount->sub($subtrahend->amount), $this->currency);
    }

    /**
     * @param $multiplier
     * @param int $rounding_mode
     * @return Money
     * @throws \Exception
     */
    public function multiply($multiplier, $rounding_mode = self::ROUND_HALF_UP)
    {
        $this->assertOperand($multiplier);
        $this->assertRoundingMode($rounding_mode);

        $product = $this->amount->mul(Decimal::create($multiplier));

        switch ($rounding_mode) {
            case self::ROUND_HALF_UP:
                return new self($product->ceil(), $this->currency);
                break;
            case PHP_ROUND_HALF_DOWN:
                return new self($product->floor(), $this->currency);
                break;
            default:
                throw new \Exception('Not implemented');
        }
    }

    /**
     * @param $operand
     * @throws \Money\InvalidArgumentException
     */
    private function assertOperand($operand)
    {
        if (!is_int($operand) && !is_float($operand)) {
            throw new InvalidArgumentException('Operand should be an integer or a float');
        }
    }

    /**
     * @param $rounding_mode
     * @throws \Money\InvalidArgumentException
     */
    private function assertRoundingMode($rounding_mode)
    {
        if (!in_array($rounding_mode, array(self::ROUND_HALF_DOWN, self::ROUND_HALF_EVEN, self::ROUND_HALF_ODD, self::ROUND_HALF_UP))) {
            throw new InvalidArgumentException('Rounding mode should be Money::ROUND_HALF_DOWN | Money::ROUND_HALF_EVEN | Money::ROUND_HALF_ODD | Money::ROUND_HALF_UP');
        }
    }

    /**
     * @param $divisor
     * @param int $rounding_mode
     * @return Money
     * @throws \Exception
     */
    public function divide($divisor, $rounding_mode = self::ROUND_HALF_UP)
    {
        $this->assertOperand($divisor);
        $this->assertRoundingMode($rounding_mode);

        $quotient = $this->amount->div(Decimal::create($divisor));
        $fractionalPart = $quotient->abs()->sub($quotient->floor()->abs());

        switch ($rounding_mode) {
            case self::ROUND_HALF_UP:
                if ($fractionalPart->comp(Decimal::fromFloat(0.5)) == 1) {
                    // fractionalPart > 0.5
                    return new self($quotient->ceil(), $this->currency);
                } else {
                    // fractionalPart <= 0.5
                    return new self($quotient->floor(), $this->currency);
                }
                break;
            case PHP_ROUND_HALF_DOWN:
                if ($fractionalPart->comp(Decimal::fromFloat(0.5)) == -1) {
                    // fractionalPart < 0.5
                    return new self($quotient->ceil(), $this->currency);
                } else {
                    // fractionalPart >= 0.5
                    return new self($quotient->floor(), $this->currency);
                }
                break;
            default:
                throw new \Exception('Not implemented');
        }
    }

    /**
     * Allocate the money according to a list of ratio's
     * @param array $ratios List of ratio's
     * @return \Money\Money
     */
    public function allocate(array $ratios)
    {
        $remainder = $this->amount;
        $results = array();
        $total = array_sum($ratios);

        foreach ($ratios as $ratio) {
            $share = $this->amount->mul(Decimal::fromFloat((float)$ratio))->div(Decimal::fromFloat((float)$total))->floor();
            $results[] = new BigMoney($share, $this->currency);
            $remainder = $remainder->sub($share);
        }

        $i = 0;
        while ($remainder->comp(Decimal::fromInteger(0)) == 1) {
            $results[$i] = new BigMoney($results[$i]->amount->add(Decimal::fromInteger(1)), $this->currency);
            /** @var Decimal $remainder */
            $remainder = $remainder->sub(Decimal::fromInteger(1));
            $i++;
        }

        return $results;
    }

    /** @return bool */
    public function isZero()
    {
        return $this->amount === 0;
    }

    /** @return bool */
    public function isPositive()
    {
        return $this->amount > 0;
    }

    /** @return bool */
    public function isNegative()
    {
        return $this->amount < 0;
    }
}
