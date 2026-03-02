<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add wishlist_item table for boutique wishlist feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE wishlist_item (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, added_at DATETIME NOT NULL, UNIQUE INDEX user_product_unique (user_id, product_id), INDEX IDX_4A5D6A8FA76ED395 (user_id), INDEX IDX_4A5D6A8F4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_4A5D6A8FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_4A5D6A8F4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY FK_4A5D6A8FA76ED395');
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY FK_4A5D6A8F4584665A');
        $this->addSql('DROP TABLE wishlist_item');
    }
}
