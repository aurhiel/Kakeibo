<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211015144143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add details field for recurrent transaction (aka. transaction_auto)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction_auto ADD details VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction_auto DROP details');
    }
}
