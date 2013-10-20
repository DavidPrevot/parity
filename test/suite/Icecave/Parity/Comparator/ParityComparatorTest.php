<?php
namespace Icecave\Parity\Comparator;

use Eloquent\Liberator\Liberator;
use Phake;
use PHPUnit_Framework_TestCase;
use stdClass;

class ParityComparatorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->fallbackComparator = Phake::mock(__NAMESPACE__ . '\ComparatorInterface');
        $this->comparator = new ParityComparator($this->fallbackComparator);

        Phake::when($this->fallbackComparator)
            ->compare(Phake::anyParameters())
            ->thenReturn(-1);
    }

    public function testCompareInversion()
    {
        $lhs = Phake::mock('Icecave\Parity\AnyComparableInterface');

        Phake::when($lhs)
            ->compare(Phake::anyParameters())
            ->thenReturn(-1);

        $this->assertSame(-1, $this->comparator->compare($lhs, 10));
        $this->assertSame(+1, $this->comparator->compare(10, $lhs));
    }

    public function testCompareWithFallback()
    {
        $result = $this->comparator->compare(10, 20);

        Phake::verify($this->fallbackComparator)->compare(10, 20);

        $this->assertSame($result, -1);
    }

    public function testCompareWithAnyComparable()
    {
        $comparable = Phake::mock('Icecave\Parity\AnyComparableInterface');

        Phake::when($comparable)
            ->compare(Phake::anyParameters())
            ->thenReturn(-10);

        $result = $this->comparator->compare($comparable, 10);

        Phake::verify($comparable)->compare(10);
        Phake::verifyNoInteraction($this->fallbackComparator);

        $this->assertSame($result, -10);
    }

    public function testCompareWithRestrictedComparable()
    {
        $comparable = Phake::mock('Icecave\Parity\RestrictedComparableInterface');

        Phake::when($comparable)
            ->compare(Phake::anyParameters())
            ->thenReturn(-10);

        Phake::when($comparable)
            ->canCompare(Phake::anyParameters())
            ->thenReturn(true);

        $result = $this->comparator->compare($comparable, 10);

        Phake::inOrder(
            Phake::verify($comparable)->canCompare(10),
            Phake::verify($comparable)->compare(10)
        );

        Phake::verifyNoInteraction($this->fallbackComparator);

        $this->assertSame($result, -10);
    }

    public function testCompareWithRestrictedComparableAndUnsupportedOperand()
    {
        $comparable = Phake::mock('Icecave\Parity\RestrictedComparableInterface');

        Phake::when($comparable)
            ->compare(Phake::anyParameters())
            ->thenReturn(-10);

        Phake::when($comparable)
            ->canCompare(Phake::anyParameters())
            ->thenReturn(false);

        $result = $this->comparator->compare($comparable, 10);

        Phake::verify($comparable)->canCompare(10);
        Phake::verify($comparable, Phake::never())->compare(Phake::anyParameters());
        Phake::verify($this->fallbackComparator)->compare($comparable, 10);

        $this->assertSame($result, -1);
    }

    public function testCompareWithSelfComparable()
    {
        $lhsComparable = Phake::mock('Icecave\Parity\SelfComparableInterface');
        $rhsComparable = clone $lhsComparable;

        Phake::when($lhsComparable)
            ->compare(Phake::anyParameters())
            ->thenReturn(-10);

        $result = $this->comparator->compare($lhsComparable, $rhsComparable);

        Phake::verify($lhsComparable)->compare($rhsComparable);
        Phake::verifyNoInteraction($this->fallbackComparator);

        $this->assertSame($result, -10);
    }

    public function testCompareWithSelfComparableUsesCache()
    {
        $lhsComparable = Phake::mock('Icecave\Parity\SelfComparableInterface');
        $rhsComparable = clone $lhsComparable;

        Phake::when($lhsComparable)
            ->compare(Phake::anyParameters())
            ->thenReturn(-10);

        $this->assertSame(-10, $this->comparator->compare($lhsComparable, $rhsComparable));
        $this->assertSame(-10, $this->comparator->compare($lhsComparable, $rhsComparable));

        $this->assertSame(
            Liberator::liberate($this->comparator)->compareImplementationClasses[get_class($lhsComparable)],
            get_class($lhsComparable)
        );
    }

    public function testCompareWithSelfComparableAndNonObject()
    {
        $comparable = Phake::mock('Icecave\Parity\SelfComparableInterface');

        $result = $this->comparator->compare($comparable, 10);

        Phake::verify($comparable, Phake::never())->compare(Phake::anyParameters());
        Phake::verify($this->fallbackComparator)->compare($comparable, 10);

        $this->assertSame($result, -1);
    }

    public function testCompareWithSelfComparableAndDifferentType()
    {
        $comparable = Phake::mock('Icecave\Parity\SelfComparableInterface');

        $result = $this->comparator->compare($comparable, new stdClass);

        Phake::verify($comparable, Phake::never())->compare(Phake::anyParameters());
        Phake::verify($this->fallbackComparator)->compare($comparable, new stdClass);

        $this->assertSame($result, -1);
    }

    // public function testCompareWithSelfComparableOnRHS()
    // {
    //     $rhs = Phake::mock('Icecave\Parity\SelfComparableInterface');

    //     Phake::when($rhs)
    //         ->compare(Phake::anyParameters())
    //         ->thenReturn(10);

    //     Phake::when($rhs)
    //         ->canCompare(Phake::anyParameters())
    //         ->thenReturn(true);

    //     $result = $this->comparator->compare(10, $rhs);

    //     Phake::inOrder(
    //         Phake::verify($rhs)->canCompare(10),
    //         Phake::verify($rhs)->compare(10)
    //     );

    //     Phake::verifyNoInteraction($this->fallbackComparator);

    //     $this->assertSame($result, -10);
    // }

}
