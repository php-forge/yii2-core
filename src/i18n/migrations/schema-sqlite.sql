/**
 * Database schema required by \yii\i18n\DbMessageSource.
 */

drop table if exists `source_message`;
drop table if exists `message`;

CREATE TABLE `source_message`
(
   `id`          integer NOT NULL,
   `category`    varchar(255),
   `message`     text
   CONSTRAINT pk_source_message PRIMARY KEY (`id`)
);

CREATE TABLE `message`
(
   `id`          integer NOT NULL,
   `language`    varchar(16) NOT NULL,
   `translation` text,
   PRIMARY KEY (`id`, `language`)
   CONSTRAINT fk_message_source_message FOREIGN KEY (`id`) REFERENCES `source_message` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
);

CREATE INDEX idx_message_language ON message (language);
CREATE INDEX idx_source_message_category ON source_message (category);
