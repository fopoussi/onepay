<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250115112527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE app_settings_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE failed_transaction_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE login_history_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE notification_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE operator_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE transaction_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE user_id_seq CASCADE');
        $this->addSql('CREATE TABLE onepay_app_settings (id SERIAL NOT NULL, key VARCHAR(50) NOT NULL, value VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE onepay_failed_transaction (id SERIAL NOT NULL, transaction_id_id INT DEFAULT NULL, error_message TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_74BDC0A3DE774E17 ON onepay_failed_transaction (transaction_id_id)');
        $this->addSql('CREATE TABLE onepay_login_history (id SERIAL NOT NULL, user_id_id INT DEFAULT NULL, ip_address VARCHAR(50) NOT NULL, device_info VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6A4176D59D86650F ON onepay_login_history (user_id_id)');
        $this->addSql('CREATE TABLE onepay_notification (id SERIAL NOT NULL, user_id_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, message TEXT NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_15797309D86650F ON onepay_notification (user_id_id)');
        $this->addSql('CREATE TABLE onepay_operator (id SERIAL NOT NULL, name VARCHAR(50) NOT NULL, commission_rate DOUBLE PRECISION DEFAULT NULL, api_endpoint VARCHAR(255) DEFAULT NULL, api_key VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE onepay_transaction (id SERIAL NOT NULL, user_id_id INT DEFAULT NULL, transaction_type VARCHAR(50) NOT NULL, amount DOUBLE PRECISION DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, from_operator VARCHAR(50) NOT NULL, to_operator VARCHAR(50) NOT NULL, receiver_phone VARCHAR(20) DEFAULT NULL, operator VARCHAR(50) DEFAULT NULL, phone_number VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7AC6EFA39D86650F ON onepay_transaction (user_id_id)');
        $this->addSql('CREATE TABLE onepay_user (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone_number VARCHAR(20) NOT NULL, password VARCHAR(255) NOT NULL, two_factor_enabled BOOLEAN DEFAULT NULL, balance DOUBLE PRECISION DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, update_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE onepay_failed_transaction ADD CONSTRAINT FK_74BDC0A3DE774E17 FOREIGN KEY (transaction_id_id) REFERENCES onepay_transaction (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE onepay_login_history ADD CONSTRAINT FK_6A4176D59D86650F FOREIGN KEY (user_id_id) REFERENCES onepay_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE onepay_notification ADD CONSTRAINT FK_15797309D86650F FOREIGN KEY (user_id_id) REFERENCES onepay_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE onepay_transaction ADD CONSTRAINT FK_7AC6EFA39D86650F FOREIGN KEY (user_id_id) REFERENCES onepay_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE failed_transaction DROP CONSTRAINT fk_8756847de774e17');
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT fk_bf5476ca9d86650f');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT fk_723705d19d86650f');
        $this->addSql('ALTER TABLE login_history DROP CONSTRAINT fk_37976e369d86650f');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE failed_transaction');
        $this->addSql('DROP TABLE operator');
        $this->addSql('DROP TABLE app_settings');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE login_history');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE app_settings_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE failed_transaction_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE login_history_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE notification_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE operator_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE transaction_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone_number VARCHAR(20) NOT NULL, password VARCHAR(255) NOT NULL, two_factor_enabled BOOLEAN DEFAULT NULL, balance DOUBLE PRECISION DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE failed_transaction (id SERIAL NOT NULL, transaction_id_id INT DEFAULT NULL, error_message TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_8756847de774e17 ON failed_transaction (transaction_id_id)');
        $this->addSql('CREATE TABLE operator (id SERIAL NOT NULL, name VARCHAR(50) NOT NULL, commission_rate DOUBLE PRECISION NOT NULL, api_endpoint VARCHAR(255) DEFAULT NULL, api_key VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE app_settings (id SERIAL NOT NULL, key VARCHAR(50) NOT NULL, value VARCHAR(255) NOT NULL, description TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE notification (id SERIAL NOT NULL, user_id_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, message TEXT NOT NULL, status VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_bf5476ca9d86650f ON notification (user_id_id)');
        $this->addSql('CREATE TABLE transaction (id SERIAL NOT NULL, user_id_id INT NOT NULL, transaction_type VARCHAR(50) NOT NULL, amount DOUBLE PRECISION NOT NULL, status VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, from_operator VARCHAR(50) NOT NULL, to_operator VARCHAR(50) DEFAULT NULL, receiver_phone VARCHAR(20) DEFAULT NULL, operator VARCHAR(50) DEFAULT NULL, phone_number VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_723705d19d86650f ON transaction (user_id_id)');
        $this->addSql('CREATE TABLE login_history (id SERIAL NOT NULL, user_id_id INT DEFAULT NULL, ip_address VARCHAR(50) NOT NULL, device_info VARCHAR(255) NOT NULL, create_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_37976e369d86650f ON login_history (user_id_id)');
        $this->addSql('ALTER TABLE failed_transaction ADD CONSTRAINT fk_8756847de774e17 FOREIGN KEY (transaction_id_id) REFERENCES transaction (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT fk_bf5476ca9d86650f FOREIGN KEY (user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT fk_723705d19d86650f FOREIGN KEY (user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE login_history ADD CONSTRAINT fk_37976e369d86650f FOREIGN KEY (user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE onepay_failed_transaction DROP CONSTRAINT FK_74BDC0A3DE774E17');
        $this->addSql('ALTER TABLE onepay_login_history DROP CONSTRAINT FK_6A4176D59D86650F');
        $this->addSql('ALTER TABLE onepay_notification DROP CONSTRAINT FK_15797309D86650F');
        $this->addSql('ALTER TABLE onepay_transaction DROP CONSTRAINT FK_7AC6EFA39D86650F');
        $this->addSql('DROP TABLE onepay_app_settings');
        $this->addSql('DROP TABLE onepay_failed_transaction');
        $this->addSql('DROP TABLE onepay_login_history');
        $this->addSql('DROP TABLE onepay_notification');
        $this->addSql('DROP TABLE onepay_operator');
        $this->addSql('DROP TABLE onepay_transaction');
        $this->addSql('DROP TABLE onepay_user');
    }
}
