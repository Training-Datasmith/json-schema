<?php

declare (strict_types=1);
/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Json_Schema\Entity;

use Json_Schema\Exception\InvalidArgumentException;
/**
 * @package JsonSchema\Entity
 *
 * @author Joost Nijhuis <jnijhuis81@gmail.com>
 */
class Json_Pointer
{
    /** @var string */
    private $filename;
    /** @var string[] */
    private $property_paths = [];
    /**
     * @var bool Whether the value at this path was set from a schema default
     */
    private $from_default = false;
    /**
     * @param string $value
     *
     * @throws InvalidArgumentException when $value is not a string
     */
    public function __construct($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Ref value must be a string');
        }
        $split_ref = explode('#', $value, 2);
        $this->filename = $split_ref[0];
        if (array_key_exists(1, $split_ref)) {
            $this->property_paths = $this->decode_property_paths($split_ref[1]);
        }
    }
    /**
     * @return string[]
     */
    private function decode_property_paths(string $property_path_string): array
    {
        $paths = [];
        foreach (explode('/', trim($property_path_string, '/')) as $path) {
            $path = $this->decode_path($path);
            if (is_string($path) && '' !== $path) {
                $paths[] = $path;
            }
        }
        return $paths;
    }
    private function encode_property_paths(): array
    {
        return array_map([$this, 'encodePath'], $this->get_property_paths());
    }
    private function decode_path(string $path): string
    {
        return strtr($path, ['~1' => '/', '~0' => '~', '%25' => '%']);
    }
    /**
     * @param string $path
     */
    private function encode_path($path): string
    {
        return strtr($path, ['/' => '~1', '~' => '~0', '%' => '%25']);
    }
    /**
     * @return string
     */
    public function get_filename()
    {
        return $this->filename;
    }
    /**
     * @return string[]
     */
    public function get_property_paths()
    {
        return $this->property_paths;
    }
    public function with_property_paths(array $property_paths): self
    {
        $new = clone $this;
        $new->property_paths = array_map(function ($p): string {
            return (string) $p;
        }, $property_paths);
        return $new;
    }
    public function get_property_path_as_string(): string
    {
        return rtrim('#/' . implode('/', $this->encode_property_paths()), '/');
    }
    public function __toString(): string
    {
        return $this->get_filename() . $this->get_property_path_as_string();
    }
    /**
     * Mark the value at this path as being set from a schema default
     */
    public function set_from_default(): void
    {
        $this->from_default = true;
    }
    /**
     * Check whether the value at this path was set from a schema default
     *
     * @return bool
     */
    public function from_default()
    {
        return $this->from_default;
    }
}