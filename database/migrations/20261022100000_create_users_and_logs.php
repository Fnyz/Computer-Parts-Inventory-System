<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersAndLogs extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(<<<'SQL'
            CREATE TABLE users (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username      VARCHAR(50)  NOT NULL,
                name          VARCHAR(150) NOT NULL,
                email         VARCHAR(150) NULL,
                phone         VARCHAR(30)  NULL,
                password_hash VARCHAR(255) NOT NULL,
                role          VARCHAR(20)  NOT NULL DEFAULT 'employee',
                is_active     TINYINT(1)   NOT NULL DEFAULT 1,
                theme         VARCHAR(10)  NOT NULL DEFAULT 'system',
                last_login_at DATETIME     NULL,
                created_at    DATETIME     NOT NULL,
                updated_at    DATETIME     NOT NULL,
                UNIQUE KEY uq_users_username (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->execute(<<<'SQL'
            CREATE TABLE activity_log (
                id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id      INT UNSIGNED NULL,
                action       VARCHAR(100) NOT NULL,
                subject_type VARCHAR(50)  NOT NULL DEFAULT '',
                subject_id   INT UNSIGNED NULL,
                details      TEXT         NULL,
                ip           VARCHAR(45)  NULL,
                created_at   DATETIME     NOT NULL,
                KEY idx_activity_created (created_at),
                KEY idx_activity_subject (subject_type, subject_id),
                CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        $this->execute(<<<'SQL'
            CREATE TABLE login_attempts (
                id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username     VARCHAR(50) NOT NULL,
                ip           VARCHAR(45) NOT NULL,
                attempted_at DATETIME    NOT NULL,
                KEY idx_login_attempts (username, ip, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

        // Sequential document numbers such as SO-2026-00042, one counter per prefix and year
        $this->execute(<<<'SQL'
            CREATE TABLE sequences (
                name       VARCHAR(20)  NOT NULL,
                period     VARCHAR(10)  NOT NULL,
                next_value INT UNSIGNED NOT NULL,
                PRIMARY KEY (name, period)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS sequences, login_attempts, activity_log, users');
    }
}
