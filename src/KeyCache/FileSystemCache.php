<?php
/**
 * @file
 * This is part of Chullo service.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Islandora\Crayfish\KeyCache;

use Beryllium\CacheBundle\Client\FileCacheClient;

/**
 * Implementation of the IUuidCache using a file system cache.
 *
 * @author Jared Whiklo <jwhiklo@gmail.com>
 * @since 2016-06-20
 */
class FileSystemCache implements IUuidCache
{

    private $cache;

    /**
     * Constructor
     *
     * @var string $path
     *   The directory to store cache in, should be writable by the webserver.
     */
    public function __construct($path)
    {
        $this->cache = new FileCacheClient($path);
    }

    /**
     * {@inheritdoc}
     */
    public function set($txID, $uuid, $path, $expire = 3600)
    {
        $cache_content = $this->cache->get($txID);
        if ($cache_content) {
            $cache_content[$uuid] = $path;
        } else {
            $cache_content = array($uuid => $path);
        }
        $this->cache->set($txID, $cache_content, $expire);
    }

    /**
     * {@inheritdoc}
     */
    public function getByUuid($txID, $uuid)
    {
       $cache_content = $this->cache->get($txID);
       if ($cache_content) {
           if (isset($cache_content[$uuid])) {
               return $cache_content[$uuid];
           }
       }
       return false;
    }

    /**
     * {@inheridoc}
     */
    public function getByPath($txID, $path)
    {
        $cache_content = $this->cache->get($txID);
        if ($cache_content) {
            $cache_content_flipped = array_flip($cache_content);
            if (isset($cache_content_flipped[$path])) {
                return $cache_content_flipped[$path];
            }
        }
        return false;
    }

    /**
     * {@inheridoc}
     */
    public function delete($txID)
    {
        $this->cache->delete($txID);
    }

    /**
     * {@inheridoc}
     */
    public function extend($txID, $seconds)
    {
        $cache_content = $this->cache->get($txID);
        if ($cache_content) {
            $this->cache->set($txId, $cache_content, $seconds);
            return true;
        }
        return false;
    }

}
