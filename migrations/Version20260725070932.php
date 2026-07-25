<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260725070932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE registration_board ADD people_who_bring_snack JSON DEFAULT '[]'::json");
        $this->addSql("UPDATE registration_board SET people_who_bring_snack = '[]'::json WHERE people_who_bring_snack IS NULL");
        $this->addSql('ALTER TABLE registration_board ALTER COLUMN people_who_bring_snack SET NOT NULL');
        $this->addSql('ALTER TABLE registration_board ALTER COLUMN people_who_bring_snack DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE registration_board DROP people_who_bring_snack');
    }
}
