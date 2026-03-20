<?php

declare (strict_types=1);
/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Json_Schema\Uri;

use Json_Schema\Exception\Uri_Resolver_Exception;
use Json_Schema\Uri_Resolver_Interface;
/**
 * Resolves JSON Schema URIs
 *
 * @author Sander Coolen <sander@jibber.nl>
 */
class Uri_Resolver implements Uri_Resolver_Interface
{
    /**
     * Parses a URI into five main components
     *
     * @param string $uri
     */
    public function parse($uri): array
    {
        preg_match('|^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?|', (string) $uri, $match);
        $components = [];
        if (5 < count($match)) {
            $components = ['scheme' => $match[2], 'authority' => $match[4], 'path' => $match[5]];
        }
        if (7 < count($match)) {
            $components['query'] = $match[7];
        }
        if (9 < count($match)) {
            $components['fragment'] = $match[9];
        }
        return $components;
    }
    /**
     * Builds a URI based on n array with the main components
     *
     *
     */
    public function generate(array $components): string
    {
        $uri = $components['scheme'] . '://' . $components['authority'] . $components['path'];
        if (array_key_exists('query', $components) && strlen($components['query'])) {
            $uri .= '?' . $components['query'];
        }
        if (array_key_exists('fragment', $components)) {
            $uri .= '#' . $components['fragment'];
        }
        return $uri;
    }
    /**
     * {@inheritdoc}
     */
    public function resolve($uri, $base_uri = null)
    {
        // treat non-uri base as local file path
        if (!is_null($base_uri) && !filter_var($base_uri, \FILTER_VALIDATE_URL) && !preg_match('|^[^/]+://|u', $base_uri)) {
            if (is_file($base_uri)) {
                $base_uri = 'file://' . realpath($base_uri);
            } elseif (is_dir($base_uri)) {
                $base_uri = 'file://' . realpath($base_uri) . '/';
            } else {
                $base_uri = 'file://' . getcwd() . '/' . $base_uri;
            }
        }
        if ($uri == '') {
            return $base_uri;
        }
        $components = $this->parse($uri);
        $path = $components['path'];
        if (!empty($components['scheme'])) {
            return $uri;
        }
        $base_components = $this->parse($base_uri);
        $base_path = $base_components['path'];
        $base_components['path'] = self::combine_relative_path_with_base_path($path, $base_path);
        if (isset($components['fragment'])) {
            $base_components['fragment'] = $components['fragment'];
        }
        return $this->generate($base_components);
    }
    /**
     * Tries to glue a relative path onto an absolute one
     *
     * @param string $relativePath
     * @param string $basePath
     *
     * @throws UriResolverException
     *
     * @return string Merged path
     */
    public static function combine_relative_path_with_base_path($relative_path, $base_path)
    {
        $relative_path = self::normalize_path($relative_path);
        if (!$relative_path) {
            return $base_path;
        }
        if ($relative_path[0] === '/') {
            return $relative_path;
        }
        if (!$base_path) {
            throw new Uri_Resolver_Exception(sprintf("Unable to resolve URI '%s' from base '%s'", $relative_path, $base_path));
        }
        $dirname = $base_path[strlen($base_path) - 1] === '/' ? $base_path : dirname($base_path);
        $combined = rtrim($dirname, '/') . '/' . ltrim($relative_path, '/');
        $combined_segments = explode('/', $combined);
        $collapsed_segments = [];
        while ($combined_segments) {
            $segment = array_shift($combined_segments);
            if ($segment === '..') {
                if (count($collapsed_segments) <= 1) {
                    // Do not remove the top level (domain)
                    // This is not ideal - the domain should not be part of the path here. parse() and generate()
                    // should handle the "domain" separately, like the schema.
                    // Then the if-condition here would be `if (!$collapsedSegments) {`.
                    throw new Uri_Resolver_Exception(sprintf("Unable to resolve URI '%s' from base '%s'", $relative_path, $base_path));
                }
                array_pop($collapsed_segments);
            } else {
                $collapsed_segments[] = $segment;
            }
        }
        return implode('/', $collapsed_segments);
    }
    /**
     * Normalizes a URI path component by removing dot-slash and double slashes
     *
     * @param string $path
     *
     * @return string
     */
    private static function normalize_path($path): ?string
    {
        $path = preg_replace('|((?<!\.)\./)*|', '', $path);
        return preg_replace('|//|', '/', $path);
    }
    /**
     * @param string $uri
     */
    public function is_valid($uri): bool
    {
        $components = $this->parse($uri);
        return !empty($components);
    }
}