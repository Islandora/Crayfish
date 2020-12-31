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
     * @var string
     */
    protected $drupalDomain;

    /**
     * @var string
     */
    protected $fedoraDomain;

    /**
     * UrlMapper constructor.
     * @param \Doctrine\DBAL\Connection $connection
     * @param string $drupalDomain
     * @param string $fedoraDomain
     */
    public function __construct(
        Connection $connection,
        $drupalDomain = "",
        $fedoraDomain = ""
    ) {
        $this->connection = $connection;
        $this->drupalDomain = $drupalDomain;
        $this->fedoraDomain = $fedoraDomain;
    }

    /**
     * {@inheritDoc}
     */
    public function getUrls($uuid)
    {
        $sql = 'SELECT drupal_uri as drupal, fedora_uri as fedora FROM Gemini WHERE uuid = :uuid';
        $result = $this->connection->fetchAssoc(
            $sql,
            ['uuid' => $uuid]
        );

        if (!empty($this->drupalDomain) && isset($result['drupal'])) {
            $result['drupal'] = $this->replaceDomain($result['drupal'], $this->drupalDomain);
        }

        if (!empty($this->fedoraDomain) && isset($result['fedora'])) {
            $result['fedora'] = $this->replaceDomain($result['fedora'], $this->fedoraDomain);
        }

        return $result;
    }

    private function replaceDomain($url, $domain)
    {
        $parts = parse_url($url);
        return "$parts[scheme]://$domain$parts[path]";
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
        $now = date("Y-m-d H:i:s", time());
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

    /**
     * {@inheritdoc}
     */
    public function findUrls($uri)
    {
        $parts = parse_url($uri);
        $path = $parts['path'];

        $query =
          'SELECT fedora_uri FROM Gemini WHERE drupal_uri LIKE :path union
           SELECT drupal_uri FROM Gemini WHERE fedora_uri LIKE :path';

        $result = $this->connection->fetchAssoc(
            $query,
            ['path' => "%$path"]
        );

        if (isset($result['fedora_uri'])) {
            if (!empty($this->fedoraDomain)) {
                $result['fedora_uri'] = $this->replaceDomain($result['fedora_uri'], $this->fedoraDomain);
            }
            $result['uri'] = $result['fedora_uri'];
            unset($result['fedora_uri']);
        }

        if (isset($result['drupal_uri'])) {
            if (!empty($this->drupalDomain)) {
                $result['drupal_uri'] = $this->replaceDomain($result['drupal_uri'], $this->drupalDomain);
            }
            $result['uri'] = $result['drupal_uri'];
            unset($result['drupal_uri']);
        }

        return $result;
    }
}
