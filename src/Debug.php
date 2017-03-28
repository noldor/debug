<?php

namespace Noldors\Helpers;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Some helpful functions for debugging.
 *
 * @package Noldors\Helpers
 */
class Debug
{
    protected static $styles = [
        'default'   => 'background-color:#fff; color:#222; line-height:1.2em; font-weight:normal; font:12px Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:100000',
        'num'       => 'color:#a71d5d',
        'const'     => 'color:#795da3',
        'str'       => 'color:#df5000',
        'cchr'      => 'color:#222',
        'note'      => 'color:#a71d5d',
        'ref'       => 'color:#a0a0a0',
        'public'    => 'color:#795da3',
        'protected' => 'color:#795da3',
        'private'   => 'color:#795da3',
        'meta'      => 'color:#b729d9',
        'key'       => 'color:#df5000',
        'index'     => 'color:#a71d5d',
    ];

    /**
     * Dump variables and kill script.
     *
     * @param mixed $value
     */
    public static function dd($value)
    {
        array_map(function ($x) {
            static::dumpVars($x);
        }, func_get_args());

        die(1);
    }

    /**
     * Dump vars to console or browser.
     *
     * @param mixed $value
     */
    public static function dumpVars($value)
    {
        if (class_exists(CliDumper::class)) {
            if (PHP_SAPI === 'cli') {
                $dumper = new CliDumper();
            } else {
                $dumper = new HtmlDumper();
                $dumper->setStyles(static::$styles);
            }

            $dumper->dump((new VarCloner())->cloneVar($value));
        } else {
            var_dump($value);
        }
    }

    /**
     * Test speed of some callable functions. You can pass as many functions as you want.
     *
     * @param int      $iterations
     * @param callable $function
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public static function testSpeed($iterations, $function)
    {
        if (!is_int($iterations) || $iterations < 1) {
            throw new \InvalidArgumentException('Number of iterations must be integer and greater than 0');
        }

        $speeds = [];
        $functionsContent = [];

        $functions = array_slice(func_get_args(), 1);

        foreach ($functions as $function) {
            if (!is_callable($function)) {
                throw new \InvalidArgumentException('All functions passed to method should be callable');
            }

            $start = microtime(true);

            for ($i = 0; $i <= $iterations; $i++) {
                $function();
            }

            $stop = microtime(true);

            $speeds[] = $stop - $start;
            $functionsContent[] = static::getFunctionContent($function);

            unset($i, $function);
        }

        $timings = static::makeResponse($speeds, $functionsContent);

        static::dd($timings);
    }

    /**
     * Get filename and lines where function stored.
     *
     * @param $function
     *
     * @return string
     */
    protected static function getFunctionContent($function)
    {
        $functionReflection = new \ReflectionFunction($function);

        return (string)$functionReflection;
    }

    /**
     * Make response array for testSpeed method.
     *
     * @param $speeds
     * @param $functionsContent
     *
     * @return array
     */
    protected static function makeResponse($speeds, $functionsContent)
    {
        return [
            'min'        => [
                'time'     => min($speeds),
                'function' => $functionsContent[array_search(min($speeds), $speeds)]
            ],
            'max'        => [
                max($speeds),
                'function' => $functionsContent[array_search(max($speeds), $speeds)]
            ],
            'executions' => $speeds
        ];
    }
}