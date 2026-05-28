<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528033642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE boutique (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(180) NOT NULL, description VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, photo_couverture VARCHAR(255) DEFAULT NULL, user_id_id INT DEFAULT NULL, categorie_id_id INT NOT NULL, UNIQUE INDEX UNIQ_A1223C549D86650F (user_id_id), UNIQUE INDEX UNIQ_A1223C548A3C7387 (categorie_id_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(120) NOT NULL, created_at DATETIME NOT NULL, photo VARCHAR(100) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(120) NOT NULL, prenom VARCHAR(120) DEFAULT NULL, email VARCHAR(190) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, telephone VARCHAR(40) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE boutique ADD CONSTRAINT FK_A1223C549D86650F FOREIGN KEY (user_id_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE boutique ADD CONSTRAINT FK_A1223C548A3C7387 FOREIGN KEY (categorie_id_id) REFERENCES categorie (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE boutique DROP FOREIGN KEY FK_A1223C549D86650F');
        $this->addSql('ALTER TABLE boutique DROP FOREIGN KEY FK_A1223C548A3C7387');
        $this->addSql('DROP TABLE boutique');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE user');
    }
}
