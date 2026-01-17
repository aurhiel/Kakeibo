<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260117011942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove no more useful balance field on bank accounts & an index on transaction to optimize balance calculation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_account DROP balance');
        $this->addSql('CREATE INDEX idx_balance_lookup ON transaction (bank_account_id, date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_account ADD balance NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('UPDATE bank_account ba SET ba.balance = (
            SELECT COALESCE(SUM(t.amount), 0) FROM transaction t
                WHERE t.bank_account_id = ba.id
        )');
        $this->addSql('DROP INDEX idx_balance_lookup ON transaction');
    }
}
