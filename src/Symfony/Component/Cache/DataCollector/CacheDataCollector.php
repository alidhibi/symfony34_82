<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\DataCollector;

use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapterEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CacheDataCollector extends DataCollector implements LateDataCollectorInterface
{
    /**
     * @var TraceableAdapter[]
     */
    private array $instances = [];

    /**
     * @param string $name
     */
    public function addInstance($name, TraceableAdapter $instance): void
    {
        $this->instances[$name] = $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null): void
    {
        $empty = ['calls' => [], 'config' => [], 'options' => [], 'statistics' => []];
        $this->data = ['instances' => $empty, 'total' => $empty];
        foreach ($this->instances as $name => $instance) {
            $this->data['instances']['calls'][$name] = $instance->getCalls();
        }

        $this->data['instances']['statistics'] = $this->calculateStatistics();
        $this->data['total']['statistics'] = $this->calculateTotalStatistics();
    }

    public function reset(): void
    {
        $this->data = [];
        foreach ($this->instances as $instance) {
            $instance->clearCalls();
        }
    }

    public function lateCollect(): void
    {
        $this->data = $this->cloneVar($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'cache';
    }

    /**
     * Method returns amount of logged Cache reads: "get" calls.
     *
     * @return array
     */
    public function getStatistics()
    {
        return $this->data['instances']['statistics'];
    }

    /**
     * Method returns the statistic totals.
     *
     * @return array
     */
    public function getTotals()
    {
        return $this->data['total']['statistics'];
    }

    /**
     * Method returns all logged Cache call objects.
     *
     * @return mixed
     */
    public function getCalls()
    {
        return $this->data['instances']['calls'];
    }

    private function calculateStatistics(): array
    {
        $statistics = [];
        foreach ($this->data['instances']['calls'] as $name => $calls) {
            $statistics[$name] = [
                'calls' => 0,
                'time' => 0,
                'reads' => 0,
                'writes' => 0,
                'deletes' => 0,
                'hits' => 0,
                'misses' => 0,
            ];
            /** @var TraceableAdapterEvent $call */
            foreach ($calls as $call) {
                ++$statistics[$name]['calls'];
                $statistics[$name]['time'] += $call->end - $call->start;
                if ('getItem' === $call->name) {
                    ++$statistics[$name]['reads'];
                    if ($call->hits) {
                        ++$statistics[$name]['hits'];
                    } else {
                        ++$statistics[$name]['misses'];
                    }
                } elseif ('getItems' === $call->name) {
                    $statistics[$name]['reads'] += $call->hits + $call->misses;
                    $statistics[$name]['hits'] += $call->hits;
                    $statistics[$name]['misses'] += $call->misses;
                } elseif ('hasItem' === $call->name) {
                    ++$statistics[$name]['reads'];
                    if (false === $call->result) {
                        ++$statistics[$name]['misses'];
                    } else {
                        ++$statistics[$name]['hits'];
                    }
                } elseif ('save' === $call->name) {
                    ++$statistics[$name]['writes'];
                } elseif ('deleteItem' === $call->name) {
                    ++$statistics[$name]['deletes'];
                }
            }

            if ($statistics[$name]['reads']) {
                $statistics[$name]['hit_read_ratio'] = round(100 * $statistics[$name]['hits'] / $statistics[$name]['reads'], 2);
            } else {
                $statistics[$name]['hit_read_ratio'] = null;
            }
        }

        return $statistics;
    }

    private function calculateTotalStatistics(): array
    {
        $statistics = $this->getStatistics();
        $totals = [
            'calls' => 0,
            'time' => 0,
            'reads' => 0,
            'writes' => 0,
            'deletes' => 0,
            'hits' => 0,
            'misses' => 0,
        ];
        foreach (array_keys($statistics) as $name) {
            foreach (array_keys($totals) as $key) {
                $totals[$key] += $statistics[$name][$key];
            }
        }

        $totals['hit_read_ratio'] = $totals['reads'] ? round(100 * $totals['hits'] / $totals['reads'], 2) : null;

        return $totals;
    }
}
