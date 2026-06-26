<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260626123805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_FFB7A9A26C6E55B5 ON demande_inscription (nom)');
        $this->addSql('CREATE INDEX IDX_FFB7A9A2A625945B ON demande_inscription (prenom)');
        $this->addSql('CREATE INDEX IDX_FFB7A9A2E7927C74 ON demande_inscription (email)');
        $this->addSql('CREATE INDEX IDX_78AF0ACC6C6E55B5 ON domaine (nom)');
        $this->addSql('CREATE INDEX IDX_BA930D696C6E55B5 ON profession (nom)');
        $this->addSql('CREATE INDEX IDX_9078B75DFF7747B4 ON protocole (titre)');
        $this->addSql('CREATE INDEX IDX_8FA4097C6C6E55B5 ON rubrique (nom)');
        $this->addSql('CREATE INDEX IDX_9775E7086C6E55B5 ON theme (nom)');
        $this->addSql('CREATE INDEX IDX_8D93D6496C6E55B5 ON user (nom)');
        $this->addSql('CREATE INDEX IDX_8D93D649A625945B ON user (prenom)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_FFB7A9A26C6E55B5 ON demande_inscription');
        $this->addSql('DROP INDEX IDX_FFB7A9A2A625945B ON demande_inscription');
        $this->addSql('DROP INDEX IDX_FFB7A9A2E7927C74 ON demande_inscription');
        $this->addSql('DROP INDEX IDX_78AF0ACC6C6E55B5 ON domaine');
        $this->addSql('DROP INDEX IDX_BA930D696C6E55B5 ON profession');
        $this->addSql('DROP INDEX IDX_9078B75DFF7747B4 ON protocole');
        $this->addSql('DROP INDEX IDX_8FA4097C6C6E55B5 ON rubrique');
        $this->addSql('DROP INDEX IDX_9775E7086C6E55B5 ON theme');
        $this->addSql('DROP INDEX IDX_8D93D6496C6E55B5 ON `user`');
        $this->addSql('DROP INDEX IDX_8D93D649A625945B ON `user`');
    }
}
