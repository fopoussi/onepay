<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250208225919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE onepay_user ADD google_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE onepay_user ADD avatar VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE onepay_user ALTER password DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE onepay_user DROP google_id');
        $this->addSql('ALTER TABLE onepay_user DROP avatar');
        $this->addSql('ALTER TABLE onepay_user ALTER password SET NOT NULL');
    }
}
