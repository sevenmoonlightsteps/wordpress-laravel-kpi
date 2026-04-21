-- Create the Laravel database if it does not already exist.
-- The wordpress database is created automatically by the MYSQL_DATABASE env var.
CREATE DATABASE IF NOT EXISTS `laravel`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Grant the shared app user access to both databases.
GRANT ALL PRIVILEGES ON `wordpress`.* TO '${DB_USER}'@'%';
GRANT ALL PRIVILEGES ON `laravel`.*   TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
