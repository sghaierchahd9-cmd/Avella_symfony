<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528110650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE boutique DROP FOREIGN KEY `FK_A1223C548A3C7387`');
        $this->addSql('ALTER TABLE boutique DROP FOREIGN KEY `FK_A1223C549D86650F`');
        $this->addSql('DROP INDEX UNIQ_A1223C549D86650F ON boutique');
        $this->addSql('DROP INDEX UNIQ_A1223C548A3C7387 ON boutique');
        $this->addSql('ALTER TABLE boutique CHANGE user_id_id user_id INT DEFAULT NULL, CHANGE categorie_id_id categorie_id INT NOT NULL');
        $this->addSql('ALTER TABLE boutique ADD CONSTRAINT FK_A1223C54A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE boutique ADD CONSTRAINT FK_A1223C54BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A1223C54A76ED395 ON boutique (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A1223C54BCF5E72D ON boutique (categorie_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE boutique DROP FOREIGN KEY FK_A1223C54A76ED395');
        $this->addSql('ALTER TABLE boutique DROP FOREIGN KEY FK_A1223C54BCF5E72D');
        $this->addSql('DROP INDEX UNIQ_A1223C54A76ED395 ON boutique');
        $this->addSql('DROP INDEX UNIQ_A1223C54BCF5E72D ON boutique');
        $this->addSql('ALTER TABLE boutique CHANGE user_id user_id_id INT DEFAULT NULL, CHANGE categorie_id categorie_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE boutique ADD CONSTRAINT `FK_A1223C548A3C7387` FOREIGN KEY (categorie_id_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE boutique ADD CONSTRAINT `FK_A1223C549D86650F` FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A1223C549D86650F ON boutique (user_id_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A1223C548A3C7387 ON boutique (categorie_id_id)');
    }
}
