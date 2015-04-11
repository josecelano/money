<?php
/**
 * This file is part of the Money library
 *
 * Copyright (c) 2011-2013 Mathias Verraes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Money\Tests;

use Litipk\BigNumbers\Decimal;
use Money\BigMoney;
use Money\Currency;
use Money\InvalidArgumentException;
use PHPUnit_Framework_TestCase;

class DecimalMoneyTest extends PHPUnit_Framework_TestCase
{
    const PHP_INT_MAX_32_BITS = '2147483647';
    const PHP_INT_MAX_64_BITS = '9223372036854775807';

    public static function provideStrings()
    {
        return array(
            array("1000", 100000),
            array("1000.0", 100000),
            array("1000.00", 100000),
            array("0.01", 1),
            array("1", 100),
            array("-1000", -100000),
            array("-1000.0", -100000),
            array("-1000.00", -100000),
            array("-0.01", -1),
            array("-1", -100),
            array("+1000", 100000),
            array("+1000.0", 100000),
            array("+1000.00", 100000),
            array("+0.01", 1),
            array("+1", 100),
            array(".99", 99),
            array("-.99", -99),
        );
    }

    public function testFactoryMethods()
    {
        $this->assertEquals(
            BigMoney::EUR(25),
            BigMoney::EUR(10)->add(BigMoney::EUR(15))
        );
        $this->assertEquals(
            BigMoney::USD(25),
            BigMoney::USD(10)->add(BigMoney::USD(15))
        );
    }

    public function testGetters()
    {
        $m = new BigMoney(Decimal::fromInteger(100), $euro = new Currency('EUR'));
        $this->assertEquals(Decimal::fromInteger(100), $m->getAmount());
        $this->assertEquals($euro, $m->getCurrency());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testDecimalsThrowException()
    {
        BigMoney::fromInteger(0.01, new Currency('EUR'));
    }

    //TODO: add tests for constructors exceptions

    public function testEquality()
    {
        $m1 = new BigMoney(Decimal::fromInteger(100), new Currency('EUR'));
        $m2 = new BigMoney(Decimal::fromInteger(100), new Currency('EUR'));
        $m3 = new BigMoney(Decimal::fromInteger(100), new Currency('USD'));
        $m4 = new BigMoney(Decimal::fromInteger(50), new Currency('EUR'));

        $this->assertTrue($m1->equals($m2));
        $this->assertFalse($m1->equals($m3));
        $this->assertFalse($m1->equals($m4));
    }

    public function testAddition()
    {
        $m1 = new BigMoney(Decimal::fromInteger(100), new Currency('EUR'));
        $m2 = new BigMoney(Decimal::fromInteger(100), new Currency('EUR'));
        $sum = $m1->add($m2);
        $expected = new BigMoney(Decimal::fromInteger(200), new Currency('EUR'));

        $this->assertEquals($expected, $sum);

        // Should return a new instance
        $this->assertNotSame($sum, $m1);
        $this->assertNotSame($sum, $m2);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testDifferentCurrenciesCannotBeAdded()
    {
        $m1 = new BigMoney(Decimal::fromInteger(100), new Currency('EUR'));
        $m2 = new BigMoney(Decimal::fromInteger(100), new Currency('USD'));
        $m1->add($m2);
    }

    public function testSubtraction()
    {
        $m1 = new BigMoney(Decimal::fromInteger(100), new Currency('EUR'));
        $m2 = new BigMoney(Decimal::fromInteger(200), new Currency('EUR'));
        $diff = $m1->subtract($m2);
        $expected = new BigMoney(Decimal::fromInteger(-100), new Currency('EUR'));

        $this->assertEquals($expected, $diff);

        // Should return a new instance
        $this->assertNotSame($diff, $m1);
        $this->assertNotSame($diff, $m2);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testDifferentCurrenciesCannotBeSubtracted()
    {
        $m1 = new BigMoney(Decimal::fromInteger(100), new Currency('EUR'));
        $m2 = new BigMoney(Decimal::fromInteger(100), new Currency('USD'));
        $m1->subtract($m2);
    }

    public function testMultiplication()
    {
        $m = new BigMoney(Decimal::fromInteger(1), new Currency('EUR'));

        $this->assertEquals(
            new BigMoney(Decimal::fromInteger(2), new Currency('EUR')),
            $m->multiply(1.5)
        );
        $this->assertEquals(
            new BigMoney(Decimal::fromInteger(1), new Currency('EUR')),
            $m->multiply(1.5, BigMoney::ROUND_HALF_DOWN)
        );

        $this->assertNotSame($m, $m->multiply(2));
    }

    public function testDivision()
    {
        $m = new BigMoney(Decimal::fromInteger(10), new Currency('EUR'));
        $this->assertEquals(
            new BigMoney(Decimal::fromInteger(3), new Currency('EUR')),
            $m->divide(3)
        );
        // TODO: Decimal does not support these round modes
        /*$this->assertEquals(
            new BigMoney(Decimal::fromInteger(2), new Currency('EUR')),
            $m->divide(4, BigMoney::ROUND_HALF_EVEN)
        );
        $this->assertEquals(
            new BigMoney(Decimal::fromInteger(3), new Currency('EUR')),
            $m->divide(3, BigMoney::ROUND_HALF_ODD)
        );*/

        $this->assertNotSame($m, $m->divide(2));
    }

    public function testComparison()
    {
        $euro1 = new BigMoney(Decimal::fromInteger(1), new Currency('EUR'));
        $euro2 = new BigMoney(Decimal::fromInteger(2), new Currency('EUR'));
        $usd1 = new BigMoney(Decimal::fromInteger(1), new Currency('USD'));
        $usd2 = new BigMoney(Decimal::fromInteger(2), new Currency('USD'));

        // EUR

        $this->assertTrue($euro2->greaterThan($euro1));
        $this->assertFalse($euro1->greaterThan($euro2));
        $this->assertTrue($euro1->lessThan($euro2));
        $this->assertFalse($euro2->lessThan($euro1));

        $this->assertEquals(-1, $euro1->compare($euro2));
        $this->assertEquals(1, $euro2->compare($euro1));
        $this->assertEquals(0, $euro1->compare($euro1));

        // USD

        $this->assertTrue($usd2->greaterThan($usd1));
        $this->assertFalse($usd1->greaterThan($usd2));
        $this->assertTrue($usd1->lessThan($usd2));
        $this->assertFalse($usd2->lessThan($usd1));

        $this->assertEquals(-1, $usd1->compare($usd2));
        $this->assertEquals(1, $usd2->compare($usd1));
        $this->assertEquals(0, $usd1->compare($usd1));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testDifferentCurrenciesCannotBeCompared()
    {
        BigMoney::EUR(1)->compare(BigMoney::USD(1));
    }

    public function testAllocation()
    {
        $m = new BigMoney(Decimal::fromInteger(100), new Currency('EUR'));
        list($part1, $part2, $part3) = $m->allocate(array(1, 1, 1));
        $this->assertEquals(new BigMoney(Decimal::fromInteger(34), new Currency('EUR')), $part1);
        $this->assertEquals(new BigMoney(Decimal::fromInteger(33), new Currency('EUR')), $part2);
        $this->assertEquals(new BigMoney(Decimal::fromInteger(33), new Currency('EUR')), $part3);

        $m = new BigMoney(Decimal::fromInteger(101), new Currency('EUR'));
        list($part1, $part2, $part3) = $m->allocate(array(1, 1, 1));
        $this->assertEquals(new BigMoney(Decimal::fromInteger(34), new Currency('EUR')), $part1);
        $this->assertEquals(new BigMoney(Decimal::fromInteger(34), new Currency('EUR')), $part2);
        $this->assertEquals(new BigMoney(Decimal::fromInteger(33), new Currency('EUR')), $part3);
    }

    public function testAllocationOrderIsImportant()
    {

        $m = new BigMoney(Decimal::fromInteger(5), new Currency('EUR'));
        list($part1, $part2) = $m->allocate(array(3, 7));
        $this->assertEquals(new BigMoney(Decimal::fromInteger(2), new Currency('EUR')), $part1);
        $this->assertEquals(new BigMoney(Decimal::fromInteger(3), new Currency('EUR')), $part2);

        $m = new BigMoney(Decimal::fromInteger(5), new Currency('EUR'));
        list($part1, $part2) = $m->allocate(array(7, 3));
        $this->assertEquals(new BigMoney(Decimal::fromInteger(4), new Currency('EUR')), $part1);
        $this->assertEquals(new BigMoney(Decimal::fromInteger(1), new Currency('EUR')), $part2);
    }

    public function testComparators()
    {
        $this->assertTrue(BigMoney::EUR(0)->isZero());
        $this->assertTrue(BigMoney::EUR(-1)->isNegative());
        $this->assertTrue(BigMoney::EUR(1)->isPositive());
        $this->assertFalse(BigMoney::EUR(1)->isZero());
        $this->assertFalse(BigMoney::EUR(1)->isNegative());
        $this->assertFalse(BigMoney::EUR(-1)->isPositive());
    }

    /**
     * @dataProvider provideStrings
     */
    public function testStringToUnits($string, $units)
    {
        $this->assertEquals($units, BigMoney::stringToUnits($string));
    }
}
