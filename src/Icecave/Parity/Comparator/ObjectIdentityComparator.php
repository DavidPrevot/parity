<?php
namespace Icecave\Parity\Comparator;

use Icecave\Parity\TypeCheck\TypeCheck;

/**
 * A comparator that compares objects by identity.
 */
class ObjectIdentityComparator implements ComparatorInterface
{
    /**
     * @param ComparatorInterface $fallbackComparator The comparator to use for non-objects.
     */
    public function __construct(ComparatorInterface $fallbackComparator)
    {
        $this->typeCheck = TypeCheck::get(__CLASS__, func_get_args());

        $this->fallbackComparator = $fallbackComparator;
    }

    /**
     * Fetch the fallback comparator.
     *
     * @return ComparatorInterface The comparator to use for non-objects.
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
     * If either of the operands is not an object the fallback comparator is
     * used.
     *
     * @param mixed $lhs The first value to compare.
     * @param mixed $rhs The second value to compare.
     *
     * @return integer The result of the comparison.
     */
    public function compare($lhs, $rhs)
    {
        TypeCheck::get(__CLASS__)->compare(func_get_args());

        if (!is_object($lhs) || !is_object($rhs)) {
            return $this->fallbackComparator()->compare($lhs, $rhs);
        }

        return strcmp(
            spl_object_hash($lhs),
            spl_object_hash($rhs)
        );

    }

    /**
     * An alias for compare().
     *
     * @param mixed $lhs The first value to compare.
     * @param mixed $rhs The second value to compare.
     *
     * @return integer The result of the comparison.
     */
    public function __invoke($lhs, $rhs)
    {
        $this->typeCheck->validateInvoke(func_get_args());

        return $this->compare($lhs, $rhs);
    }

    private $typeCheck;
    private $fallbackComparator;
}