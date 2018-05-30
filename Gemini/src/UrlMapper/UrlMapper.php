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
        $sql = 'SELECT drupal_uri as drupal, fedora_uri as fedora FROM Gemini WHERE uuid = :uuid';
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
        $drupal_uri,
        $fedora_uri
    ) {
        $this->connection->beginTransaction();
        // Hash incomming URIs
        $fedora_hash = hash('sha512', $fedora_uri);
        $drupal_hash = hash('sha512', $drupal_uri);
        $now = time();
        $db_data = [
          'uuid' => $uuid,
          'drupal_uri' => $drupal_uri,
          'fedora_uri' => $fedora_uri,
          'drupal_hash' => $drupal_hash,
          'fedora_hash' => $fedora_hash,
          'dateCreated' => $now,
          'dateUpdated' => $now,
        ];

        try {
            // Try to insert first, and if the record already exists, update it.
            try {
                $this->connection->insert('Gemini', $db_data);
                $this->connection->commit();
                return true;
            } catch (UniqueConstraintViolationException $e) {
                // We want to maintain the creation UNIX Timestamp
                unset($db_data['dateCreated']);
                unset($db_data['uuid']);
                $this->connection->update('Gemini', $db_data, ['uuid' => $uuid]);
                $this->connection->commit();
                return false;
            }
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteUrls($uuid)
    {
        $this->connection->beginTransaction();

        try {
            $count = $this->connection->delete(
                'Gemini',
                ['uuid' => $uuid]
            );

            $this->connection->commit();

            return $count > 0;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
