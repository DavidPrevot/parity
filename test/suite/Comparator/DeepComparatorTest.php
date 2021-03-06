<?php
namespace Icecave\Parity\Comparator;

use DateTime;
use Icecave\Parity\ChildObject;
use Icecave\Parity\ParentObject;
use Phake;
use PHPUnit_Framework_TestCase;
use stdClass;

class DeepComparatorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->fallbackComparator = Phake::partialMock(__NAMESPACE__ . '\PhpComparator');
        $this->comparator = new DeepComparator($this->fallbackComparator);
    }

    public function testInvoke()
    {
        $this->assertSame(
            0,
            call_user_func(
                $this->comparator,
                array(1, 2, 3),
                array(1, 2, 3)
            )
        );
    }

    public function testCompareWithObjectReferences()
    {
        $value = (object) array('foo' => 'bar');

        $result = $this->comparator->compare($value, $value);

        // Even though there are strings in the object, the fallback comparator
        // should never be used because we are comparing references to the same
        // object.
        Phake::verifyNoInteraction($this->fallbackComparator);

        $this->assertSame(0, $result);
    }

    public function testCompareWithEmptyArrays()
    {
        $result = $this->comparator->compare(array(), array());

        Phake::verifyNoInteraction($this->fallbackComparator);

        $this->assertSame(0, $result);
    }

    public function testCompareWithArrays()
    {
        $this->assertSame(
            0,
            $this->comparator->compare(
                array(1, 2, 3),
                array(1, 2, 3)
            )
        );
    }

    public function testCompareWithAssociativeArrays()
    {
        $this->assertSame(
            0,
            $this->comparator->compare(
                array('a' => 1, 'b' => 2, 'c' => 3),
                array('a' => 1, 'b' => 2, 'c' => 3)
            )
        );
    }

    public function testCompareWithArraysThatDifferBySize()
    {
        $this->assertLessThan(
            0,
            $this->comparator->compare(
                array(1, 2),
                array(1, 2, 3)
            )
        );

        $this->assertGreaterThan(
            0,
            $this->comparator->compare(
                array(1, 2, 3),
                array(1, 2)
            )
        );
    }

    public function testCompareWithArraysThatDifferBySizeAndContent()
    {
        $this->assertLessThan(
            0,
            $this->comparator->compare(
                array(1, 2, 3),
                array(1, 3)
            )
        );

        $this->assertGreaterThan(
            0,
            $this->comparator->compare(
                array(1, 3),
                array(1, 2, 3)
            )
        );
    }

    public function testCompareWithArraysThatDifferByKeys()
    {
        $this->assertLessThan(
            0,
            $this->comparator->compare(
                array('a' => 1),
                array('b' => 1)
            )
        );

        $this->assertGreaterThan(
            0,
            $this->comparator->compare(
                array('b' => 1),
                array('a' => 1)
            )
        );
    }

    public function testCompareWithObjects()
    {
        $this->assertSame(
            0,
            $this->comparator->compare(
                (object) array('a' => 1, 'b' => 2, 'c' => 3),
                (object) array('a' => 1, 'b' => 2, 'c' => 3)
            )
        );
    }

    public function testCompareWithObjectsThatDifferBySize()
    {
        $this->assertLessThan(
            0,
            $this->comparator->compare(
                (object) array('a' => 1, 'b' => 2),
                (object) array('a' => 1, 'b' => 2, 'c' => 3)
            )
        );

        $this->assertGreaterThan(
            0,
            $this->comparator->compare(
                (object) array('a' => 1, 'b' => 2, 'c' => 3),
                (object) array('a' => 1, 'b' => 2)
            )
        );
    }

    public function testCompareWithObjectsThatDifferBySizeAndContent()
    {
        $this->assertLessThan(
            0,
            $this->comparator->compare(
                (object) array('a' => 1, 'b' => 2, 'c' => 3),
                (object) array('a' => 1, 'b' => 3)
            )
        );

        $this->assertGreaterThan(
            0,
            $this->comparator->compare(
                (object) array('a' => 1, 'b' => 3),
                (object) array('a' => 1, 'b' => 2, 'c' => 3)
            )
        );
    }

    public function testCompareWithObjectsThatDifferClassName()
    {
        $this->assertLessThan(
            0,
            $this->comparator->compare(
                new DateTime(),
                new stdClass()
            )
        );

        $this->assertGreaterThan(
            0,
            $this->comparator->compare(
                new stdClass(),
                new DateTime()
            )
        );
    }

    public function testCompareWithObjectsWithRelaxedClassComparisons()
    {
        $this->comparator = new DeepComparator($this->fallbackComparator, true);

        $this->assertSame(
            0,
            $this->comparator->compare(
                new ParentObject(1, 2),
                new ChildObject(1, 2)
            )
        );
    }

    public function testCompareWithObjectsDifferentInnerClassTypes()
    {
        $obj1 = new stdClass();
        $obj1->foo = new stdClass();

        $obj2 = new stdClass();
        $obj2->foo = new ParentObject(0, 0);

        $this->assertSame(0, $this->comparator->compare($obj1, $obj1));
        $this->assertSame(0, $this->comparator->compare($obj2, $obj2));

        $this->assertLessThan(0, $this->comparator->compare($obj2, $obj1));
        $this->assertGreaterThan(0, $this->comparator->compare($obj1, $obj2));
    }

    public function testCompareWithObjectsParentAndDerived()
    {
        $obj1 = new ParentObject(0, 0);
        $obj2 = new ChildObject(0, 0);

        $this->assertSame(0, $this->comparator->compare($obj1, $obj1));
        $this->assertSame(0, $this->comparator->compare($obj2, $obj2));

        $this->assertLessThan(0, $this->comparator->compare($obj2, $obj1));
        $this->assertGreaterThan(0, $this->comparator->compare($obj1, $obj2));
    }

    public function testCompareWithObjectsHavingSharedInnerObject()
    {
        $shared = new ParentObject('foo', 'bar');

        $obj1 = new ParentObject(111, $shared);
        $obj2 = new ParentObject(222, $shared);

        $obj3 = new ChildObject(333, $shared);
        $obj4 = new ChildObject(444, $shared);

        $this->assertSame(0, $this->comparator->compare($obj1, $obj1));
        $this->assertSame(0, $this->comparator->compare($obj3, $obj3));

        $this->assertLessThan(0, $this->comparator->compare($obj1, $obj2));
        $this->assertGreaterThan(0, $this->comparator->compare($obj2, $obj1));

        $this->assertLessThan(0, $this->comparator->compare($obj3, $obj4));
        $this->assertGreaterThan(0, $this->comparator->compare($obj4, $obj3));
    }

    public function testCompareWithSimpleRecursion()
    {
        $obj1 = new stdClass();
        $obj1->foo = $obj1;
        $obj1->bar = 1;

        $obj2 = new stdClass();
        $obj2->foo = $obj2;
        $obj2->bar = 2;

        // The first property compared is infinitely recusive, so just the hash will be used.
        // Since the hash's wont match the 'bar' property will not be compared.
        if (spl_object_hash($obj1) < spl_object_hash($obj2)) {
            $this->assertLessThan(0, $this->comparator->compare($obj1, $obj2));
            $this->assertGreaterThan(0, $this->comparator->compare($obj2, $obj1));
        } else {
            $this->assertLessThan(0, $this->comparator->compare($obj2, $obj1));
            $this->assertGreaterThan(0, $this->comparator->compare($obj1, $obj2));
        }
    }

    public function testCompareWithSimpleObjectsDoubleRecursion()
    {
        $obj1 = new stdClass();
        $obj1->recurse = new stdClass();
        $obj1->recurse->recurse = $obj1;
        $obj1->value = 1;

        $obj2 = new stdClass();
        $obj2->recurse = new stdClass();
        $obj2->recurse->recurse = $obj2;
        $obj2->value = 2;

        // The first property compared is infinitely recusive, so just the hash will be used.
        // Since the hash's wont match the 'value' property will not be compared.
        if (spl_object_hash($obj1) < spl_object_hash($obj2)) {
            $this->assertLessThan(0, $this->comparator->compare($obj1, $obj2));
            $this->assertGreaterThan(0, $this->comparator->compare($obj2, $obj1));
        } else {
            $this->assertLessThan(0, $this->comparator->compare($obj2, $obj1));
            $this->assertGreaterThan(0, $this->comparator->compare($obj1, $obj2));
        }
    }

    public function testCompareWithSimpleObjectsBothHavingObject1AsFirstProperty()
    {
        $obj1 = new stdClass();
        $obj1->foo = $obj1;
        $obj1->bar = 1;

        $obj2 = new stdClass();
        $obj2->foo = $obj1;
        $obj2->bar = 2;

        $this->assertLessThan(0, $this->comparator->compare($obj1, $obj2));
        $this->assertGreaterThan(0, $this->comparator->compare($obj2, $obj1));
    }

    public function testCompareWithObjectCycle()
    {
        $obj1 = new stdClass();
        $obj1->foo = new ParentObject('foo1', $obj1);

        $obj2 = new stdClass();
        $obj2->foo = new ParentObject('foo2', $obj2);

        $obj3 = new stdClass();
        $obj3->foo = new ChildObject('bar3', $obj1);

        $obj4 = new stdClass();
        $obj4->foo = new ChildObject('bar4', $obj2);

        $this->assertLessThan(0, $this->comparator->compare($obj1, $obj2));
        $this->assertGreaterThan(0, $this->comparator->compare($obj2, $obj1));

        $this->assertLessThan(0, $this->comparator->compare($obj3, $obj4));
        $this->assertGreaterThan(0, $this->comparator->compare($obj4, $obj3));
    }

    public function testCompareWithObjectsHavingInternalArraysAndObjects()
    {
        $shared = new ChildObject('foo', 'bar');

        $obj1 = new ParentObject(array('a', 'b'), array($shared, 'foo'));
        $obj2 = new ParentObject(array('a', 'b'), array($shared, 'foo'));
        $obj3 = new ParentObject(array('x', 'y'), array($shared, 'foo'));

        $this->assertSame(0, $this->comparator->compare($obj1, $obj1));
        $this->assertSame(0, $this->comparator->compare($obj1, $obj2));
        $this->assertSame(0, $this->comparator->compare($obj3, $obj3));

        $this->assertLessThan(0, $this->comparator->compare($obj1, $obj3));
        $this->assertGreaterThan(0, $this->comparator->compare($obj3, $obj1));
    }
}
