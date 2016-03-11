<?php

namespace Tartana\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160219102032 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE tartana_download (id INTEGER NOT NULL, link VARCHAR(255) NOT NULL, destination VARCHAR(255) NOT NULL, file_name VARCHAR(255) DEFAULT NULL, progress NUMERIC(10, 2) DEFAULT NULL, state SMALLINT NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, size INTEGER DEFAULT NULL, pid INTEGER DEFAULT NULL, message VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_630F6CFD36AC99F1 ON tartana_download (link)');
        $this->addSql('CREATE INDEX destination_idx ON tartana_download (destination)');
        $this->addSql('CREATE TABLE fos_user (id INTEGER NOT NULL, username VARCHAR(255) NOT NULL, username_canonical VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, email_canonical VARCHAR(255) NOT NULL, enabled BOOLEAN NOT NULL, salt VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, locked BOOLEAN NOT NULL, expired BOOLEAN NOT NULL, expires_at DATETIME DEFAULT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, roles CLOB NOT NULL, credentials_expired BOOLEAN NOT NULL, credentials_expire_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_957A647992FC23A8 ON fos_user (username_canonical)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_957A6479A0D96FBF ON fos_user (email_canonical)');

        $this->addSql(
        		"INSERT INTO fos_user (id, username, username_canonical, email, email_canonical, enabled, salt, password, locked, expired, credentials_expired, roles) values (1, 'admin', 'admin', 'admin', 'admin', 1, 'qbwylaz8jn4s0csggscowkoo4ssosw0', '1RILyHwvf1QmhWvNSUE2HUWs8e6Y/N3NjCnoPtBRBzkc4rpxYb7qJfOzXhKvCiUvwVMa5k19Jm4OsFVZnoARqw==', 0, 0, 0, 'a:0:{}')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE tartana_download');
        $this->addSql('DROP TABLE fos_user');
    }
}
