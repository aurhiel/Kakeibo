<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250517112610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new linked transaction to another one when making a bank transfer between 2 bank accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE scheduled_command');
        $this->addSql('ALTER TABLE transaction ADD bank_transfer_linked_transaction_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1EA0FCCAD FOREIGN KEY (bank_transfer_linked_transaction_id) REFERENCES transaction (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_723705D1EA0FCCAD ON transaction (bank_transfer_linked_transaction_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE scheduled_command (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, command VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, arguments LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, cron_expression VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, last_execution DATETIME NOT NULL, last_return_code INT DEFAULT NULL, log_file VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, priority INT NOT NULL, execute_immediately TINYINT(1) NOT NULL, disabled TINYINT(1) NOT NULL, locked TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1EA0FCCAD');
        $this->addSql('DROP INDEX UNIQ_723705D1EA0FCCAD ON transaction');
        $this->addSql('ALTER TABLE transaction DROP bank_transfer_linked_transaction_id');
    }
}
