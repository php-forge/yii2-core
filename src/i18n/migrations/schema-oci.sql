/**
 * Database schema required by \yii\i18n\DbMessageSource.
 */

drop table if exists "source_message";
drop table if exists "message";

CREATE TABLE "source_message"
(
	"id"          integer NOT NULL PRIMARY KEY,
	"category"    varchar(255),
	"message"     clob
);
CREATE SEQUENCE "source_message_SEQ";

CREATE TABLE "message"
(
	"id"          integer NOT NULL,
	"language"    varchar(16) NOT NULL,
	"translation" clob,
	primary key ("id", "language"),
	foreign key ("id") references "source_message" ("id") on delete cascade
);

CREATE INDEX idx_message_language ON "message"("language");
CREATE INDEX idx_source_message_category ON "source_message"("category");
