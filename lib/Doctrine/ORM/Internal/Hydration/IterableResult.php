<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

/**
 * Represents a result structure that can be iterated over, hydrating row-by-row
 * during the iteration. An IterableResult is obtained by AbstractHydrator#iterate().
 *
 * @author robo
 * @since 2.0
 */
class IterableResult implements \Iterator
{
    /**
     * @var \Doctrine\ORM\Internal\Hydration\AbstractHydrator
     */
    private $hydrator;

    /**
     * @var boolean
     */
    private $rewinded = false;

    /**
     * @var integer
     */
    private $key = -1;

    /**
     * @var object|null
     */
    private $current;

    /**
     * @param \Doctrine\ORM\Internal\Hydration\AbstractHydrator $hydrator
     */
    public function __construct($hydrator)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * @return void
     *
     * @throws HydrationException
     */
    public function rewind()
    {
        if ($this->rewinded == true) {
            throw new HydrationException("Can only iterate a Result once.");
        }

        $this->current = $this->next();
        $this->rewinded = true;
    }

    /**
     * Gets the next set of results.
     *
     * @return array|false
     */
    public function next()
    {
        $this->current = $this->hydrator->hydrateRow();
        $this->key++;

        return $this->current;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return ($this->current!=false);
    }
}
