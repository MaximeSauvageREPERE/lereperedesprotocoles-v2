<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260623112552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout entité Profession, remplacement du champ texte profession par une FK dans User et DemandeInscription';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE profession (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, UNIQUE INDEX UNIQ_BA930D69989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE demande_inscription ADD profession_id INT NOT NULL, DROP profession');
        $this->addSql('ALTER TABLE demande_inscription ADD CONSTRAINT FK_FFB7A9A2FDEF8996 FOREIGN KEY (profession_id) REFERENCES profession (id)');
        $this->addSql('CREATE INDEX IDX_FFB7A9A2FDEF8996 ON demande_inscription (profession_id)');
        $this->addSql('ALTER TABLE user ADD profession_id INT NOT NULL, DROP profession');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649FDEF8996 FOREIGN KEY (profession_id) REFERENCES profession (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649FDEF8996 ON user (profession_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE profession');
        $this->addSql('ALTER TABLE demande_inscription DROP FOREIGN KEY FK_FFB7A9A2FDEF8996');
        $this->addSql('DROP INDEX IDX_FFB7A9A2FDEF8996 ON demande_inscription');
        $this->addSql('ALTER TABLE demande_inscription ADD profession VARCHAR(150) NOT NULL, DROP profession_id');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649FDEF8996');
        $this->addSql('DROP INDEX IDX_8D93D649FDEF8996 ON `user`');
        $this->addSql('ALTER TABLE `user` ADD profession VARCHAR(150) NOT NULL, DROP profession_id');
    }
}
