<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260111123354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new balance stored in database instead of re-calculated each times!';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE bank_account ADD balance NUMERIC(10, 2) DEFAULT '0' NOT NULL");
        $this->addSql('UPDATE bank_account ba SET ba.balance = (
            SELECT COALESCE(SUM(t.amount), 0) FROM transaction t
                WHERE t.bank_account_id = ba.id
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_account DROP balance');
    }
}
