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
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql(
            'CREATE TABLE Gemini (fedora_hash VARCHAR(128) NOT NULL, 
            drupal_hash VARCHAR(128) NOT NULL, uuid VARCHAR(36) NOT NULL, 
            drupal_uri LONGTEXT NOT NULL, fedora_uri LONGTEXT NOT NULL, 
            dateCreated DATETIME NOT NULL, dateUpdated DATETIME NOT NULL, 
            UNIQUE KEY(fedora_hash, drupal_hash), PRIMARY KEY(uuid)) 
            DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );
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
