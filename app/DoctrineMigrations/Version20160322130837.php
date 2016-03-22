<?php

namespace Tartana\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160322130837 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX destination_idx');
        $this->addSql('DROP INDEX UNIQ_630F6CFD36AC99F1');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tartana_download AS SELECT id, link, destination, file_name, progress, state, started_at, finished_at, size, pid, message FROM tartana_download');
        $this->addSql('DROP TABLE tartana_download');
        $this->addSql('CREATE TABLE tartana_download (id INTEGER NOT NULL, link VARCHAR(255) NOT NULL COLLATE BINARY, destination VARCHAR(255) NOT NULL COLLATE BINARY, file_name VARCHAR(255) DEFAULT NULL COLLATE BINARY, progress NUMERIC(10, 2) DEFAULT NULL, state SMALLINT NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, size INTEGER DEFAULT NULL, pid INTEGER DEFAULT NULL, message VARCHAR(255) DEFAULT NULL COLLATE BINARY, hash VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO tartana_download (id, link, destination, file_name, progress, state, started_at, finished_at, size, pid, message) SELECT id, link, destination, file_name, progress, state, started_at, finished_at, size, pid, message FROM __temp__tartana_download');
        $this->addSql('DROP TABLE __temp__tartana_download');
        $this->addSql('CREATE INDEX destination_idx ON tartana_download (destination)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_52C66A9336AC99F1 ON tartana_download (link)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP INDEX UNIQ_52C66A9336AC99F1');
        $this->addSql('DROP INDEX destination_idx');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tartana_download AS SELECT id, link, destination, file_name, progress, state, started_at, finished_at, size, pid, message FROM tartana_download');
        $this->addSql('DROP TABLE tartana_download');
        $this->addSql('CREATE TABLE tartana_download (id INTEGER NOT NULL, link VARCHAR(255) NOT NULL, destination VARCHAR(255) NOT NULL, file_name VARCHAR(255) DEFAULT NULL, progress NUMERIC(10, 2) DEFAULT NULL, state SMALLINT NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, size INTEGER DEFAULT NULL, pid INTEGER DEFAULT NULL, message VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO tartana_download (id, link, destination, file_name, progress, state, started_at, finished_at, size, pid, message) SELECT id, link, destination, file_name, progress, state, started_at, finished_at, size, pid, message FROM __temp__tartana_download');
        $this->addSql('DROP TABLE __temp__tartana_download');
        $this->addSql('CREATE INDEX destination_idx ON tartana_download (destination)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_630F6CFD36AC99F1 ON tartana_download (link)');
    }
}
