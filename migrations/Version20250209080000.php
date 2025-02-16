<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250209080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs de sécurité à OnepayLoginHistory';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE onepay_login_history ADD user_agent VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE onepay_login_history ADD is_successful BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE onepay_login_history ADD failure_reason VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE onepay_login_history ADD additional_data JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE onepay_login_history DROP user_agent');
        $this->addSql('ALTER TABLE onepay_login_history DROP is_successful');
        $this->addSql('ALTER TABLE onepay_login_history DROP failure_reason');
        $this->addSql('ALTER TABLE onepay_login_history DROP additional_data');
    }
}
