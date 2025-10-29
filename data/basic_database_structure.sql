SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
--
-- Table structure for table `blocked_users`
--
CREATE TABLE IF NOT EXISTS `blocked_users`
(
    `ID`         int(11)    NOT NULL AUTO_INCREMENT,
    `user_id`    bigint(20) NOT NULL,
    `by_user_id` bigint(20) NOT NULL DEFAULT 0 COMMENT '0 is the internal user ID',
    `timestamp`  datetime   NOT NULL DEFAULT current_timestamp(),
    `blocked`    tinyint(1) NOT NULL DEFAULT 1,
    `enabled`    tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`ID`),
    KEY `user_id` (`user_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------
--
-- Table structure for table `users`
--
CREATE TABLE IF NOT EXISTS `users`
(
    `ID`         int(11)     NOT NULL AUTO_INCREMENT,
    `user_id`    bigint(20)  NOT NULL,
    `uuid`       uuid        NOT NULL DEFAULT uuid(),
    `first_name` varchar(64) NOT NULL,
    `username`   varchar(32)          DEFAULT NULL,
    `last_id`    int(11)     NOT NULL DEFAULT 0,
    `temp`       varchar(500)         DEFAULT NULL,
    `active`     tinyint(1)  NOT NULL DEFAULT 1,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `user_id` (`user_id`),
    UNIQUE KEY `uuid` (`uuid`),
    KEY `active` (`active`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_uca1400_ai_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
