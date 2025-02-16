<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250209075230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE onepay_login_history DROP CONSTRAINT fk_6a4176d59d86650f');
        $this->addSql('DROP INDEX idx_6a4176d59d86650f');
        $this->addSql('ALTER TABLE onepay_login_history ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE onepay_login_history DROP user_id_id');
        $this->addSql('ALTER TABLE onepay_login_history ALTER ip_address DROP NOT NULL');
        $this->addSql('ALTER TABLE onepay_login_history ALTER device_info DROP NOT NULL');
        $this->addSql('ALTER TABLE onepay_login_history RENAME COLUMN created_at TO login_date');
        $this->addSql('ALTER TABLE onepay_login_history ADD CONSTRAINT FK_6A4176D5A76ED395 FOREIGN KEY (user_id) REFERENCES onepay_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6A4176D5A76ED395 ON onepay_login_history (user_id)');
        // Ajouter la colonne last_name avec une valeur par défaut
        $this->addSql('ALTER TABLE onepay_user ADD last_name VARCHAR(255)');
        $this->addSql('UPDATE onepay_user SET last_name = \'\' WHERE last_name IS NULL');
        $this->addSql('ALTER TABLE onepay_user ALTER COLUMN last_name SET NOT NULL');
        
        // Ajouter is_verified avec une valeur par défaut false
        $this->addSql('ALTER TABLE onepay_user ADD is_verified BOOLEAN DEFAULT false');
        $this->addSql('ALTER TABLE onepay_user ALTER COLUMN is_verified SET NOT NULL');
        
        // Renommer name en first_name
        $this->addSql('ALTER TABLE onepay_user RENAME COLUMN name TO first_name');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE onepay_login_history DROP CONSTRAINT FK_6A4176D5A76ED395');
        $this->addSql('DROP INDEX IDX_6A4176D5A76ED395');
        $this->addSql('ALTER TABLE onepay_login_history ADD user_id_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE onepay_login_history DROP user_id');
        $this->addSql('ALTER TABLE onepay_login_history ALTER ip_address SET NOT NULL');
        $this->addSql('ALTER TABLE onepay_login_history ALTER device_info SET NOT NULL');
        $this->addSql('ALTER TABLE onepay_login_history RENAME COLUMN login_date TO created_at');
        $this->addSql('ALTER TABLE onepay_login_history ADD CONSTRAINT fk_6a4176d59d86650f FOREIGN KEY (user_id_id) REFERENCES onepay_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_6a4176d59d86650f ON onepay_login_history (user_id_id)');
        $this->addSql('ALTER TABLE onepay_user RENAME COLUMN first_name TO name');
        $this->addSql('ALTER TABLE onepay_user DROP COLUMN last_name');
        $this->addSql('ALTER TABLE onepay_user DROP COLUMN is_verified');
    }
}
