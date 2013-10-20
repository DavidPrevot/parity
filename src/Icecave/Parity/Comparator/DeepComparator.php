<?php
namespace Icecave\Parity\Comparator;

use Icecave\Parity\TypeCheck\TypeCheck;
use ReflectionObject;

/**
 * A comparator that performs deep comparison of PHP arrays and objects.
 *
 * Comparison of objects is recursion-safe.
 */
class DeepComparator implements ComparatorInterface
{
    /**
     * If $relaxClassComparisons is true, class names are not included in the
     * comparison of objects.
     *
     * @param ComparatorInterface $fallbackComparator    The comparator to use when the operands are not arrays or objects.
     * @param boolean             $relaxClassComparisons True to relax class name comparisons; false to compare strictly.
     */
    public function __construct(
        ComparatorInterface $fallbackComparator,
        $relaxClassComparisons = false
    ) {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->fallbackComparator = $fallbackComparator;
        $this->relaxClassComparisons = $relaxClassComparisons;
    }

    /**
     * Fetch the fallback comparator.
     *
     * @return The comparator to use when the operands are not arrays or objects.
     */
    public function fallbackComparator()
    {
        $this->typeCheck->fallbackComparator(func_get_args());

        return $this->fallbackComparator;
    }

    /**
     * Compare two values, yielding a result according to the following table:
     *
     * +--------------------+---------------+
     * | Condition          | Result        |
     * +--------------------+---------------+
     * | $this == $value    | $result === 0 |
     * | $this < $value     | $result < 0   |
     * | $this > $value     | $result > 0   |
     * +--------------------+---------------+
     *
     * A deep comparison is performed if both operands are arrays, or both are
     * objects; otherwise, the fallback comparator is used.
     *
     * @param mixed $lhs The first value to compare.
     * @param mixed $rhs The second value to compare.
     *
     * @return integer The result of the comparison.
     */
    public function compare($lhs, $rhs)
    {
        TypeCheck::get(__CLASS__)->compare(func_get_args());

        $visitationContext = array();

        return $this->compareValue($lhs, $rhs, $visitationContext);
    }

    /**
     * @param mixed $lhs
     * @param mixed $rhs
     * @param mixed &$visitationContext
     *
     * @return integer The result of the comparison.
     */
    protected function compareValue($lhs, $rhs, &$visitationContext)
    {
        TypeCheck::get(__CLASS__)->compareValue(func_get_args());

        if (is_array($lhs) && is_array($rhs)) {
            return $this->compareArray($lhs, $rhs, $visitationContext);
        } elseif (is_object($lhs) && is_object($rhs)) {
            return $this->compareObject($lhs, $rhs, $visitationContext);
        }

        return $this->fallbackComparator()->compare($lhs, $rhs);
    }

    /**
     * @param array $lhs
     * @param array $rhs
     * @param mixed &$visitationContext
     *
     * @return integer The result of the comparison.
     */
    protected function compareArray(array $lhs, array $rhs, &$visitationContext)
    {
        TypeCheck::get(__CLASS__)->compareArray(func_get_args());

        reset($lhs);
        reset($rhs);

        while (true) {
            $left  = each($lhs);
            $right = each($rhs);

            if ($left === false && $right === false) {
                break;
            } elseif ($left === false) {
                return -1;
            } elseif ($right === false) {
                return +1;
            }

            $cmp = $this->compareValue($left['key'], $right['key'], $visitationContext);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = $this->compareValue($left['value'], $right['value'], $visitationContext);
            if ($cmp !== 0) {
                return $cmp;
            }
        }

        return 0;
    }

    /**
     * @param object $lhs
     * @param object $rhs
     * @param mixed  &$visitationContext
     *
     * @return integer The result of the comparison.
     */
    protected function compareObject($lhs, $rhs, &$visitationContext)
    {
        TypeCheck::get(__CLASS__)->compareObject(func_get_args());

        if ($lhs === $rhs) {
            return 0;
        } elseif ($this->isNestedComparison($lhs, $rhs, $visitationContext)) {
            return strcmp(
                spl_object_hash($lhs),
                spl_object_hash($rhs)
            );
        } elseif (!$this->relaxClassComparisons) {
            $diff = strcmp(get_class($lhs), get_class($rhs));
            if ($diff !== 0) {
                return $diff;
            }
        }

        return $this->compareArray(
            $this->objectProperties($lhs, $visitationContext),
            $this->objectProperties($rhs, $visitationContext),
            $visitationContext
        );
    }

    /**
     * @param object $object
     * @param mixed  &$visitationContext
     *
     * @return array<string,mixed>
     */
    protected function objectProperties($object, &$visitationContext)
    {
        TypeCheck::get(__CLASS__)->objectProperties(func_get_args());

        $properties = array();
        $reflector = new ReflectionObject($object);

        while ($reflector) {
            foreach ($reflector->getProperties() as $property) {
                if ($property->isStatic()) {
                    continue;
                }

                $key = sprintf(
                    '%s::%s',
                    $property->getDeclaringClass()->getName(),
                    $property->getName()
                );

                $property->setAccessible(true);
                $properties[$key] = $property->getValue($object);
            }

            $reflector = $reflector->getParentClass();
        }

        return $properties;
    }

    /**
     * @param mixed $lhs
     * @param mixed $rhs
     * @param mixed &$visitationContext
     */
    protected function isNestedComparison($lhs, $rhs, &$visitationContext)
    {
        $this->typeCheck->isNestedComparison(func_get_args());

        $key = spl_object_hash($lhs) . ':' . spl_object_hash($rhs);

        if (array_key_exists($key, $visitationContext)) {
            return true;
        }

        $visitationContext[$key] = true;

        return false;
    }

    private $typeCheck;
    private $fallbackComparator;
    private $relaxClassComparisons;
}
