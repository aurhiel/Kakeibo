<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250930205615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a new field to define if a bank account is archived';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_account ADD is_archived TINYINT(1) NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_account DROP is_archived');
    }
}
