<?php
/**
 * @file
 * Part of the Chullo service
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Islandora\Crayfish\KeyCache;

/**
 * Interface for key -> value service to store UUID -> Fedora Paths
 *  not yet indexed into the triplestore (ie. in a transaction)
 * @author Jared Whiklo <jwhiklo@gmail.com>
 * @since 2016-04-12
 */
interface IUuidCache
{
  
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
    public function set($txID, $uuid, $path, $expire = 3600);

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
    public function getByUuid($txID, $uuid);

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
    public function getByPath($txID, $path);

    /**
     * Delete any pairs related to transaction ID.
     *
     * @var string $txID
      *   The transaction ID.
     * @return void
     */
    public function delete($txID);

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
    public function extend($txID, $seconds);
}
