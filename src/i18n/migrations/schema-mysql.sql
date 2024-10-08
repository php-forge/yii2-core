/**
 * Database schema required by \yii\i18n\DbMessageSource.
 */

drop table if exists `source_message`;
drop table if exists `message`;

CREATE TABLE `source_message`
(
   `id`          integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
   `category`    varchar(255),
   `message`     text
);

CREATE TABLE `message`
(
   `id`          integer NOT NULL,
   `language`    varchar(16) NOT NULL,
   `translation` text
);

ALTER TABLE `message` ADD CONSTRAINT `pk_message_id_language` PRIMARY KEY (`id`, `language`);
ALTER TABLE `message` ADD CONSTRAINT `fk_message_source_message` FOREIGN KEY (`id`) REFERENCES `source_message` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE;

CREATE INDEX idx_message_language ON message (language);
CREATE INDEX idx_source_message_category ON source_message (category);
