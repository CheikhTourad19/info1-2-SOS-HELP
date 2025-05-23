<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250523194845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE post_document DROP original_name, DROP url
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_image DROP original_name, DROP url
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE post_image ADD original_name VARCHAR(255) DEFAULT NULL, ADD url VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE post_document ADD original_name VARCHAR(255) DEFAULT NULL, ADD url VARCHAR(255) DEFAULT NULL
        SQL);
    }
}
