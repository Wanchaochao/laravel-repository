<?php
/**
 *
 * CacheTrait.php
 *
 * Create: 16-11-21 10:36
 * Editor: created by PhpStorm
 */

namespace Littlebug\Traits\Repository;

use Closure;

trait CacheTrait
{
    protected $cache_miss      = 1;
    protected $cache_hit       = 2;
    protected $cache_not_found = 0;

    protected function searchByCache(Closure $get, Closure $set)
    {
        return [$get, $set];
    }

    protected function searchNoCache()
    {
        return [null, null, null];
    }

    /***
     * @param Closure $callback
     *
     * @return array
     */
    protected function cacheMiss(Closure $callback)
    {
        return [$this->cache_miss, [], $callback];

    }

    /****
     *
     * 有缓存设置,且命中
     *
     * @param $data
     *
     * @return array
     */
    protected function cacheHit($data)
    {
        return [$this->cache_hit, $data, null];
    }

    /****
     *
     * 没有缓存设置
     *
     * @return array
     */
    protected function cacheNotFound()
    {
        return [$this->cache_not_found, [], null];
    }
}