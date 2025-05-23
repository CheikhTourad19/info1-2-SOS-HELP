<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250523193954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE post_document (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, INDEX IDX_678403D74B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE post_image (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, INDEX IDX_522688B04B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_document ADD CONSTRAINT FK_678403D74B89032C FOREIGN KEY (post_id) REFERENCES post (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_image ADD CONSTRAINT FK_522688B04B89032C FOREIGN KEY (post_id) REFERENCES post (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE post_document DROP FOREIGN KEY FK_678403D74B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_image DROP FOREIGN KEY FK_522688B04B89032C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE post_document
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE post_image
        SQL);
    }
}
