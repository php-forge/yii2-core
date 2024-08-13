DROP TABLE IF EXISTS `composite_fk` CASCADE;
DROP TABLE IF EXISTS `order_item` CASCADE;
DROP TABLE IF EXISTS `order_item_with_null_fk` CASCADE;
DROP TABLE IF EXISTS `item` CASCADE;
DROP TABLE IF EXISTS `order` CASCADE;
DROP TABLE IF EXISTS `order_with_null_fk` CASCADE;
DROP TABLE IF EXISTS `category` CASCADE;
DROP TABLE IF EXISTS `customer` CASCADE;
DROP TABLE IF EXISTS `profile` CASCADE;
DROP TABLE IF EXISTS `null_values` CASCADE;
DROP TABLE IF EXISTS `negative_default_values` CASCADE;
DROP TABLE IF EXISTS `type` CASCADE;
DROP TABLE IF EXISTS `constraints` CASCADE;
DROP TABLE IF EXISTS `animal` CASCADE;
DROP TABLE IF EXISTS `default_pk` CASCADE;
DROP TABLE IF EXISTS `document` CASCADE;
DROP TABLE IF EXISTS `comment` CASCADE;
DROP TABLE IF EXISTS `dossier`;
DROP TABLE IF EXISTS `employee`;
DROP TABLE IF EXISTS `department`;
DROP TABLE IF EXISTS `storage`;
DROP TABLE IF EXISTS `alpha`;
DROP TABLE IF EXISTS `beta`;
DROP VIEW IF EXISTS `animal_view`;
DROP TABLE IF EXISTS `T_constraints_4` CASCADE;
DROP TABLE IF EXISTS `T_constraints_3` CASCADE;
DROP TABLE IF EXISTS `T_constraints_2` CASCADE;
DROP TABLE IF EXISTS `T_constraints_1` CASCADE;
DROP TABLE IF EXISTS `T_upsert` CASCADE;
DROP TABLE IF EXISTS `T_upsert_1`;
DROP TABLE IF EXISTS `T_upsert_varbinary` CASCADE;

CREATE TABLE `T_upsert`
(
    `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `ts` BIGINT NULL,
    `email` VARCHAR(128) NOT NULL UNIQUE,
    `recovery_email` VARCHAR(128) NULL,
    `address` TEXT NULL,
    `status` TINYINT NOT NULL DEFAULT 0,
    `orders` INT NOT NULL DEFAULT 0,
    `profile_id` INT NULL,
    UNIQUE (`email`, `recovery_email`)
)
ENGINE = 'InnoDB' DEFAULT CHARSET = 'utf8mb4';

CREATE TABLE `T_upsert_1` (
  `a` int(11) NOT NULL,
  PRIMARY KEY (`a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `T_upsert_varbinary`
(
    `id` INT NOT NULL,
    `blob_col` blob,
    UNIQUE (`id`)
);

CREATE TABLE `animal` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(255) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE VIEW `animal_view` AS SELECT * FROM `animal`;

CREATE TABLE `profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(128) NOT NULL,
  `name` varchar(128),
  `address` text,
  `status` int (11) DEFAULT 0,
  `profile_id` int(11),
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_customer_profile_id` FOREIGN KEY (`profile_id`) REFERENCES `profile` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `created_at` int(11) NOT NULL,
  `total` decimal(10,0) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_order_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `animal` (`type`) VALUES ('yiiunit\data\ar\Cat');
INSERT INTO `animal` (`type`) VALUES ('yiiunit\data\ar\Dog');

INSERT INTO `profile` (description) VALUES ('profile customer 1');
INSERT INTO `profile` (description) VALUES ('profile customer 3');

INSERT INTO `customer` (email, name, address, status, profile_id) VALUES ('user1@example.com', 'user1', 'address1', 1, 1);
INSERT INTO `customer` (email, name, address, status) VALUES ('user2@example.com', 'user2', 'address2', 1);
INSERT INTO `customer` (email, name, address, status, profile_id) VALUES ('user3@example.com', 'user3', 'address3', 2, 2);

INSERT INTO `order` (customer_id, created_at, total) VALUES (1, 1325282384, 110.0);
INSERT INTO `order` (customer_id, created_at, total) VALUES (2, 1325334482, 33.0);
INSERT INTO `order` (customer_id, created_at, total) VALUES (2, 1325502201, 40.0);
