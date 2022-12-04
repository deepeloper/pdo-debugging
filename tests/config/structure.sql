DROP DATABASE IF EXISTS `pdo_debugging_tests`;
CREATE DATABASE `pdo_debugging_tests` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
CONNECT `pdo_debugging_tests`;

CREATE TABLE `test` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `date` datetime NOT NULL DEFAULT current_timestamp(),
    `foo` int(11) NOT NULL,
    `bar` varchar(255) NOT NULL,

    PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
