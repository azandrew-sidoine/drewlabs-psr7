<?php

namespace Drewlabs\Psr7;

use Drewlabs\Psr7Stream\CreatesStream;
use Drewlabs\Psr7Stream\Stream;

class CreatesURLEncodedStream implements CreatesStream
{
    /**
     * 
     * @var array<array<string,mixed>>|object
     */
    private $attributes;

    /**
     * Creates an instance of {@see \Drewlabs\Psr7\CreatesURLEncodedStream}
     * 
     * @param array $attributes 
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes ? (array)$attributes : [];
    }

    public function createStream()
    {
        return Stream::new($this->getEncodedData());
    }

    /**
     * Returns the URL encoded representation of the data
     * 
     * @return string 
     */
    public function getEncodedData()
    {
        $output = $this->isArrayList() ? self::flatten($this->attributes) :  $this->attributes;
        // \http_build_query($options['form_params'], '', '&')
        if (is_array($output) || is_object($output)) {
            $output = implode('&', array_map(function ($key, $value) {
                // Encode keys and values using urlencode() to match the default
                // behavior http_build_query() where $encoding_type is
                // PHP_QUERY_RFC1738.
                // https://github.com/php/php-src/blob/master/ext/standard/http.c
                return urlencode(strval($key)) . '=' . urlencode(strval($value));
            }, array_keys((array)$output), array_values((array)$output)));
        }
        return $output;
    }

    /**
     * Returns true if each keys of the attribute is list (array) type
     * 
     * @return bool 
     */
    private function isArrayList()
    {
        if (!is_array($this->attributes)) {
            return false;
        }
        return (bool)count(array_filter($this->attributes, 'is_array'));
    }


    /**
     * Converts input data to 1 dimensional list of values
     * 
     * @param mixed $data 
     * @param bool $prefix 
     * @return array 
     */
    private static function flatten($data, $prefix = false)
    {
        if (null === $data) {
            return [$prefix => $data];
        }
        if (empty($data)) {
            return [$prefix  =>  ''];
        }
        if (!(is_array($data) || is_object($data))) {
            return [];
        }
        $list = [];
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $list[$prefix ? $prefix . '[' . $key . ']' : $key] = $value;
                continue;
            }
            if ($value instanceof \CURLFile) {
                $list[$key] = $value;
                continue;
            }
            $list = array_merge($list, self::flatten($value, $prefix ? $prefix . '[' . $key . ']' : $key));
        }
        return $list;
    }
}
