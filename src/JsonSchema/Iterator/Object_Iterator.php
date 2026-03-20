<?php

declare (strict_types=1);
/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Json_Schema\Iterator;

/**
 * @package JsonSchema\Iterator
 *
 * @author Joost Nijhuis <jnijhuis81@gmail.com>
 */
class Object_Iterator implements \Iterator, \Countable
{
    /** @var object */
    private $object;
    /** @var int */
    private $position = 0;
    /** @var array */
    private $data = [];
    /** @var bool */
    private $initialized = false;
    /**
     * @param object $object
     */
    public function __construct($object)
    {
        $this->object = $object;
    }
    /**
     * {@inheritdoc}
     */
    #[\Return_Type_Will_Change]
    public function current()
    {
        $this->initialize();
        return $this->data[$this->position];
    }
    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->initialize();
        $this->position++;
    }
    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        $this->initialize();
        return $this->position;
    }
    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        $this->initialize();
        return isset($this->data[$this->position]);
    }
    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->initialize();
        $this->position = 0;
    }
    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $this->initialize();
        return count($this->data);
    }
    /**
     * Initializer
     */
    private function initialize(): void
    {
        if (!$this->initialized) {
            $this->data = $this->build_data_from_object($this->object);
            $this->initialized = true;
        }
    }
    /**
     * @param object $object
     */
    private function build_data_from_object($object): array
    {
        $result = [];
        $stack = new \SplStack();
        $stack->push($object);
        while (!$stack->is_empty()) {
            $current = $stack->pop();
            if (is_object($current)) {
                array_push($result, $current);
            }
            foreach ($this->get_data_from_item($current) as $property_value) {
                if (is_object($property_value) || is_array($property_value)) {
                    $stack->push($property_value);
                }
            }
        }
        return $result;
    }
    /**
     * @param object|array $item
     *
     * @return array
     */
    private function get_data_from_item($item)
    {
        if (!is_object($item) && !is_array($item)) {
            return [];
        }
        return is_object($item) ? get_object_vars($item) : $item;
    }
}