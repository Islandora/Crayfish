<?php

namespace Islandora\Gemini\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180530031926 extends AbstractMigration
{
    public function up(Schema $schema)
    {
      $this->addSql(
        'DROP TABLE IF EXISTS Gemini;'
      );

      if  ('mysql' == $this->connection->getDatabasePlatform()->getName())  {
        $this->addSql(
          'CREATE TABLE Gemini (fedora_hash VARCHAR(128) NOT NULL,
          drupal_hash VARCHAR(128) NOT NULL, uuid VARCHAR(36) NOT NULL,
          drupal_uri LONGTEXT NOT NULL, fedora_uri LONGTEXT NOT NULL,
          dateCreated DATETIME NOT NULL, dateUpdated DATETIME NOT NULL,
          UNIQUE KEY(fedora_hash, drupal_hash), PRIMARY KEY(uuid))
          DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );
      }
      elseif
      ('postgresql' == $this->connection->getDatabasePlatform()->getName())  {
        $this->addSql(
          'CREATE TABLE Gemini (
             fedora_hash VARCHAR(128) NOT NULL,
             drupal_hash VARCHAR(128) NOT NULL,
             uuid VARCHAR(36) PRIMARY KEY,
             drupal_uri TEXT NOT NULL,
             fedora_uri TEXT NOT NULL,
             dateCreated TIMESTAMP NOT NULL,
             dateUpdated TIMESTAMP NOT NULL
           );'
        );
        $this->addSql(
          'CREATE UNIQUE INDEX fedora_drupal_hash ON Gemini (fedora_hash, drupal_hash);'
        );
      }
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );
        $this->addSql('DROP TABLE Gemini');
    }
}
