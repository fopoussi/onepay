<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250114070120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_settings (id SERIAL NOT NULL, key VARCHAR(50) NOT NULL, value VARCHAR(255) NOT NULL, description TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE failed_transaction (id SERIAL NOT NULL, transaction_id_id INT DEFAULT NULL, error_message TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8756847DE774E17 ON failed_transaction (transaction_id_id)');
        $this->addSql('CREATE TABLE login_history (id SERIAL NOT NULL, user_id_id INT DEFAULT NULL, ip_address VARCHAR(50) NOT NULL, device_info VARCHAR(255) NOT NULL, create_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_37976E369D86650F ON login_history (user_id_id)');
        $this->addSql('CREATE TABLE notification (id SERIAL NOT NULL, user_id_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, message TEXT NOT NULL, status VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BF5476CA9D86650F ON notification (user_id_id)');
        $this->addSql('CREATE TABLE operator (id SERIAL NOT NULL, name VARCHAR(50) NOT NULL, commission_rate DOUBLE PRECISION NOT NULL, api_endpoint VARCHAR(255) DEFAULT NULL, api_key VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE transaction (id SERIAL NOT NULL, user_id_id INT NOT NULL, transaction_type VARCHAR(50) NOT NULL, amount DOUBLE PRECISION NOT NULL, status VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, from_operator VARCHAR(50) NOT NULL, to_operator VARCHAR(50) DEFAULT NULL, receiver_phone VARCHAR(20) DEFAULT NULL, operator VARCHAR(50) DEFAULT NULL, phone_number VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_723705D19D86650F ON transaction (user_id_id)');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone_number VARCHAR(20) NOT NULL, password VARCHAR(255) NOT NULL, two_factor_enabled BOOLEAN DEFAULT NULL, balance DOUBLE PRECISION DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE failed_transaction ADD CONSTRAINT FK_8756847DE774E17 FOREIGN KEY (transaction_id_id) REFERENCES transaction (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE login_history ADD CONSTRAINT FK_37976E369D86650F FOREIGN KEY (user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA9D86650F FOREIGN KEY (user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D19D86650F FOREIGN KEY (user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE failed_transaction DROP CONSTRAINT FK_8756847DE774E17');
        $this->addSql('ALTER TABLE login_history DROP CONSTRAINT FK_37976E369D86650F');
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CA9D86650F');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D19D86650F');
        $this->addSql('DROP TABLE app_settings');
        $this->addSql('DROP TABLE failed_transaction');
        $this->addSql('DROP TABLE login_history');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE operator');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE "user"');
    }
}
