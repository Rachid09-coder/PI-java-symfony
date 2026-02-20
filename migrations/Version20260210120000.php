<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Forum: forum_thread and forum_post tables for course discussions.
 */
final class Version20260210120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add forum_thread and forum_post tables for course forum (professors and students).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE forum_thread (
            id INT AUTO_INCREMENT NOT NULL,
            course_id INT NOT NULL,
            author_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_FORUM_THREAD_COURSE (course_id),
            INDEX IDX_FORUM_THREAD_AUTHOR (author_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_FORUM_THREAD_COURSE FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE,
            CONSTRAINT FK_FORUM_THREAD_AUTHOR FOREIGN KEY (author_id) REFERENCES `user` (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forum_post (
            id INT AUTO_INCREMENT NOT NULL,
            thread_id INT NOT NULL,
            author_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_FORUM_POST_THREAD (thread_id),
            INDEX IDX_FORUM_POST_AUTHOR (author_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_FORUM_POST_THREAD FOREIGN KEY (thread_id) REFERENCES forum_thread (id) ON DELETE CASCADE,
            CONSTRAINT FK_FORUM_POST_AUTHOR FOREIGN KEY (author_id) REFERENCES `user` (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE forum_post');
        $this->addSql('DROP TABLE forum_thread');
    }
}
