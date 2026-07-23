-- Second database for production Product dump (local dev)
CREATE DATABASE IF NOT EXISTS `product` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `product`.* TO 'product'@'%';
GRANT ALL PRIVILEGES ON `product_app`.* TO 'product'@'%';
FLUSH PRIVILEGES;
