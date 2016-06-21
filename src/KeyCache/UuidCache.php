<?php
/**
 * @file
 * Part of the Chullo service
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Islandora\Crayfish\KeyCache;

use Moust\Silex\Cache\AbstractCache;

/**
 *  For key -> value service to store UUID -> Fedora Paths
 *  not yet indexed into the triplestore (ie. in a transaction)
 * @author Jared Whiklo <jwhiklo@gmail.com>
 * @since 2016-04-12
 */
class UuidCache
{

    /**
     * @var AbstractCache $_cache
     */
    private $_cache;

    /**
     * Construtor
     *
     * @var AbstractCache $cache
     *   The supplied cache type.
     */
    public function __construct(AbstractCache $cache)
    {
       $this->_cache = $cache; 
    }

    /**
     * Sets the value for the provided key.
     *
     * @var string $txID
     *   The transaction ID.
     * @var string $uuid
     *   The UUID.
     * @var string $path
     *   The Fedora path.
     * @var int $expire
     *   The number of seconds from now to expire. Default to an hour.
     * @return bool
     *   Was key set successful.
     */
    public function set($txID, $uuid, $path, $expire = 3600)
    {
        $cache_content = $this->_cache->fetch($txID);
        if ($cache_content) {
            $cache_content = unserialize($cache_content);
            $cache_content[$uuid] = $path;
        } else {
            $cache_content = array($uuid => $path);
        }
        return $this->_cache->store($txID, serialize($cache_content), $expire);
    }

    /**
     * Gets the Fedora path for the provided UUID.
     *
     * @var string $txID
     *   The transaction ID.
     * @var string $uuid
     *   The UUID
     * @return string
     *   The UUID corresponding to the path in the transaction or NULL
     */
    public function getByUuid($txID, $uuid)
    {
        $cache_content = $this->_cache->fetch($txID);
        if ($cache_content) {
            $cache_content = unserialize($cache_content);
            if (isset($cache_content[$uuid])) {
                return $cache_content[$uuid];
            }
        }
        return null;
    }

    /**
     * Gets the UUID for the provided path.
     *
     * @var string $txID
     *   The transaction ID.
     * @var string $path
     *   The key.
     * @return string
     *   The UUID corresponding to the path in the transaction or NULL
     */
    public function getByPath($txID, $path)
    {
        $cache_content = $this->_cache->fetch($txID);
        if ($cache_content) {
            $cache_content = unserialize($cache_content);
            $cache_content_flip = array_flip($cache_content);
            if (isset($cache_content_flip[$path])) {
                return $cache_content_flip[$path];
            }
        }
        return null;
    }

    /**
     * Delete any pairs related to transaction ID.
     *
     * @var string $txID
      *   The transaction ID.
     * @return void
     */
    public function delete($txID)
    {
        $cache_content = $this->_cache->delete($txID);
    }

    /**
     * Set/reset the transaction to expire in $seconds seconds.
     *
     * @var string $txID
     *   The transaction ID.
     * @var int $seconds
     *   The number of seconds from now to expire.
     * @return bool
     *   Whether the expiry was set or not.
     */
    public function extend($txID, $seconds)
    {
        $cache_content = $this->_cache->fetch($txID);
        if ($cache_content) {
            return $this->_cache->store($txID, $cache_content, $seconds);
        }
        return false;
    }

}
