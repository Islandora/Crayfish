<?php

namespace Islandora\Gemini\Service;

use Doctrine\DBAL\Connection;

/**
 * Class GeminiService
 * @package Islandora\Gemini\Service
 */
class GeminiService implements GeminiServiceInterface
{

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * GeminiService constructor.
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function getFedoraPath($drupal_path)
    {
        $sql = "SELECT fedora FROM Gemini WHERE drupal = :path";
        $stmt = $this->connection->executeQuery(
            $sql,
            ['path' => $drupal_path]
        );
        $result = $stmt->fetch();

        if (isset($result['fedora'])) {
            return $result['fedora'];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getDrupalPath($fedora_path)
    {
        $sql = "SELECT drupal FROM Gemini WHERE fedora = :path";
        $stmt = $this->connection->executeQuery(
            $sql,
            ['path' => $fedora_path]
        );
        $result = $stmt->fetch();

        if (isset($result['drupal'])) {
            return $result['drupal'];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function createPair($drupal_path, $fedora_path)
    {
        $this->connection->insert(
            'Gemini',
            ['drupal' => $drupal_path, 'fedora' => $fedora_path]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFromDrupalPath($drupal_path)
    {
        return $this->connection->delete(
            'Gemini',
            ['drupal' => $drupal_path]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFromFedoraPath($fedora_path)
    {
        return $this->connection->delete(
            'Gemini',
            ['fedora' => $fedora_path]
        );
    }
}
