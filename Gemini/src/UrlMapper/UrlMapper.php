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
        $this->connection->beginTransaction();

        try {
            // Try to insert first, and if the record already exists, update it.
            try {
                $this->connection->insert(
                    'Gemini',
                    ['uuid' => $uuid, 'drupal' => $drupal, 'fedora' => $fedora]
                );
                $this->connection->commit();
                return true;
            } catch (UniqueConstraintViolationException $e) {
                $sql = "UPDATE Gemini SET fedora = :fedora, drupal = :drupal WHERE uuid = :uuid";
                $this->connection->executeUpdate(
                    $sql,
                    ['uuid' => $uuid, 'drupal' => $drupal, 'fedora' => $fedora]
                );
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
