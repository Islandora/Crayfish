<?php

namespace Islandora\Gemini\UrlMapper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * Class UrlMapper
 * @package Islandora\Crayfish\Commons
 */
class UrlMapper implements UrlMapperInterface
{

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * UrlMapper constructor.
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function getUrls($uuid)
    {
        $sql = 'SELECT drupal, fedora FROM Gemini WHERE uuid = :uuid';
        return $this->connection->fetchAssoc(
            $sql,
            ['uuid' => $uuid]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function saveUrls(
        $uuid,
        $drupal,
        $fedora
    ) {
        return $this->connection->transactional(function() use ($uuid, $drupal, $fedora) {
            // Try to insert first, and if the record already exists, upate it.
            try {
                return $this->connection->insert(
                    'Gemini',
                    ['uuid' => $uuid, 'drupal' => $drupal, 'fedora' => $fedora]
                );
            }
            catch (UniqueConstraintViolationException $e) {
                $sql = "UPDATE Gemini SET fedora = :fedora, drupal = :drupal WHERE uuid = :uuid";
                return $this->connection->executeUpdate(
                    $sql,
                    ['uuid' => $uuid, 'drupal' => $drupal, 'fedora' => $fedora]
                );
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function deleteUrls($uuid)
    {
        return $this->connection->transactional(function() use ($uuid) {
            return $this->connection->delete(
                'Gemini',
                ['uuid' => $uuid]
            );
        });
    }
}
