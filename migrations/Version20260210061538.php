<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210061538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE competition (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, location VARCHAR(255) NOT NULL, max_participants INT NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, winner VARCHAR(255) DEFAULT NULL, image VARCHAR(500) DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, organizer_id INT NOT NULL, INDEX IDX_B50A2CB1876C4DDA (organizer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE competition_application (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(50) NOT NULL, applied_at DATETIME NOT NULL, approved_at DATETIME DEFAULT NULL, rejected_at DATETIME DEFAULT NULL, notes LONGTEXT DEFAULT NULL, athlete_id INT NOT NULL, competition_id INT NOT NULL, INDEX IDX_8CFA72BFFE6BCB8B (athlete_id), INDEX IDX_8CFA72BF7B39D312 (competition_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE meal (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, calories INT NOT NULL, protein INT DEFAULT NULL, carbs INT DEFAULT NULL, fat INT DEFAULT NULL, image VARCHAR(500) DEFAULT NULL, created_at DATETIME NOT NULL, meal_time VARCHAR(50) NOT NULL, day_of_week INT DEFAULT NULL, coach_id INT NOT NULL, INDEX IDX_9EF68E9C3C105691 (coach_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE nutrition_plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, duration INT NOT NULL, objective LONGTEXT NOT NULL, created_at DATETIME NOT NULL, daily_water_intake INT DEFAULT NULL, coach_id INT NOT NULL, INDEX IDX_F660B5EE3C105691 (coach_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE nutrition_plan_meal (nutrition_plan_id INT NOT NULL, meal_id INT NOT NULL, INDEX IDX_74AAB48A113D03C9 (nutrition_plan_id), INDEX IDX_74AAB48A639666D6 (meal_id), PRIMARY KEY(nutrition_plan_id, meal_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(100) NOT NULL, role_type VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, water_intake JSON DEFAULT NULL, assigned_nutrition_plan_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D649AD9AC3CA (assigned_nutrition_plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE competition ADD CONSTRAINT FK_B50A2CB1876C4DDA FOREIGN KEY (organizer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE competition_application ADD CONSTRAINT FK_8CFA72BFFE6BCB8B FOREIGN KEY (athlete_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE competition_application ADD CONSTRAINT FK_8CFA72BF7B39D312 FOREIGN KEY (competition_id) REFERENCES competition (id)');
        $this->addSql('ALTER TABLE meal ADD CONSTRAINT FK_9EF68E9C3C105691 FOREIGN KEY (coach_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE nutrition_plan ADD CONSTRAINT FK_F660B5EE3C105691 FOREIGN KEY (coach_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE nutrition_plan_meal ADD CONSTRAINT FK_74AAB48A113D03C9 FOREIGN KEY (nutrition_plan_id) REFERENCES nutrition_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nutrition_plan_meal ADD CONSTRAINT FK_74AAB48A639666D6 FOREIGN KEY (meal_id) REFERENCES meal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649AD9AC3CA FOREIGN KEY (assigned_nutrition_plan_id) REFERENCES nutrition_plan (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE competition DROP FOREIGN KEY FK_B50A2CB1876C4DDA');
        $this->addSql('ALTER TABLE competition_application DROP FOREIGN KEY FK_8CFA72BFFE6BCB8B');
        $this->addSql('ALTER TABLE competition_application DROP FOREIGN KEY FK_8CFA72BF7B39D312');
        $this->addSql('ALTER TABLE meal DROP FOREIGN KEY FK_9EF68E9C3C105691');
        $this->addSql('ALTER TABLE nutrition_plan DROP FOREIGN KEY FK_F660B5EE3C105691');
        $this->addSql('ALTER TABLE nutrition_plan_meal DROP FOREIGN KEY FK_74AAB48A113D03C9');
        $this->addSql('ALTER TABLE nutrition_plan_meal DROP FOREIGN KEY FK_74AAB48A639666D6');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649AD9AC3CA');
        $this->addSql('DROP TABLE competition');
        $this->addSql('DROP TABLE competition_application');
        $this->addSql('DROP TABLE meal');
        $this->addSql('DROP TABLE nutrition_plan');
        $this->addSql('DROP TABLE nutrition_plan_meal');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
