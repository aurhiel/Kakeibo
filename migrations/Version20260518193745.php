<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260518193745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a new field to define if a bank account should be displayed on dashboard';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_account ADD show_on_dashboard TINYINT(1) NOT NULL DEFAULT true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bank_account DROP show_on_dashboard');
    }
}
