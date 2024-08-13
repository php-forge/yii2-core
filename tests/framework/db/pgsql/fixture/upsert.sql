DROP TABLE IF EXISTS "composite_fk" CASCADE;
DROP TABLE IF EXISTS "order_item" CASCADE;
DROP TABLE IF EXISTS "item" CASCADE;
DROP SEQUENCE IF EXISTS "item_id_seq_2" CASCADE;
DROP TABLE IF EXISTS "order_item_with_null_fk" CASCADE;
DROP TABLE IF EXISTS "order" CASCADE;
DROP TABLE IF EXISTS "order_with_null_fk" CASCADE;
DROP TABLE IF EXISTS "category" CASCADE;
DROP TABLE IF EXISTS "customer" CASCADE;
DROP TABLE IF EXISTS "profile" CASCADE;
DROP TABLE IF EXISTS "type" CASCADE;
DROP TABLE IF EXISTS "null_values" CASCADE;
DROP TABLE IF EXISTS "negative_default_values" CASCADE;
DROP TABLE IF EXISTS "constraints" CASCADE;
DROP TABLE IF EXISTS "bool_values" CASCADE;
DROP TABLE IF EXISTS "animal" CASCADE;
DROP TABLE IF EXISTS "default_pk" CASCADE;
DROP TABLE IF EXISTS "document" CASCADE;
DROP TABLE IF EXISTS "comment" CASCADE;
DROP TABLE IF EXISTS "dossier";
DROP TABLE IF EXISTS "employee";
DROP TABLE IF EXISTS "department";
DROP TABLE IF EXISTS "alpha";
DROP TABLE IF EXISTS "beta";
DROP VIEW IF EXISTS "animal_view";
DROP TABLE IF EXISTS "T_constraints_4";
DROP TABLE IF EXISTS "T_constraints_3";
DROP TABLE IF EXISTS "T_constraints_2";
DROP TABLE IF EXISTS "T_constraints_1";
DROP TABLE IF EXISTS "T_upsert";
DROP TABLE IF EXISTS "T_upsert_1";
DROP TABLE IF EXISTS "T_upsert_varbinary";
DROP SCHEMA IF EXISTS "schema1" CASCADE;
DROP SCHEMA IF EXISTS "schema2" CASCADE;

CREATE TABLE "T_upsert"
(
    "id" SERIAL NOT NULL PRIMARY KEY,
    "ts" BIGINT NULL,
    "email" VARCHAR(128) NOT NULL UNIQUE,
    "recovery_email" VARCHAR(128) NULL,
    "address" TEXT NULL,
    "status" SMALLINT NOT NULL DEFAULT 0,
    "orders" INT NOT NULL DEFAULT 0,
    "profile_id" INT NULL,
    UNIQUE ("email", "recovery_email")
);

CREATE TABLE "T_upsert_1"
(
    "a" INT NOT NULL PRIMARY KEY
);

CREATE TABLE "T_upsert_varbinary"
(
    "id" INT NOT NULL,
    "blob_col" BYTEA,
    UNIQUE ("id")
);

CREATE TABLE "animal" (
  id serial primary key,
  type varchar(255) not null
);

CREATE VIEW "animal_view" AS SELECT * FROM "animal";

CREATE TABLE "customer" (
  id serial not null primary key,
  email varchar(128) NOT NULL,
  name varchar(128),
  address text,
  status integer DEFAULT 0,
  bool_status boolean DEFAULT FALSE,
  profile_id integer
);

comment on column public.customer.email is 'someone@example.com';

CREATE TABLE "order" (
  id serial not null primary key,
  customer_id integer NOT NULL references "customer"(id) on UPDATE CASCADE on DELETE CASCADE,
  created_at integer NOT NULL,
  total decimal(10,0) NOT NULL
);

INSERT INTO "animal" (type) VALUES ('yiiunit\data\ar\Cat');
INSERT INTO "animal" (type) VALUES ('yiiunit\data\ar\Dog');

INSERT INTO "customer" (email, name, address, status, bool_status, profile_id) VALUES ('user1@example.com', 'user1', 'address1', 1, true, 1);
INSERT INTO "customer" (email, name, address, status, bool_status) VALUES ('user2@example.com', 'user2', 'address2', 1, true);
INSERT INTO "customer" (email, name, address, status, bool_status, profile_id) VALUES ('user3@example.com', 'user3', 'address3', 2, false, 2);

INSERT INTO "order" (customer_id, created_at, total) VALUES (1, 1325282384, 110.0);
INSERT INTO "order" (customer_id, created_at, total) VALUES (2, 1325334482, 33.0);
INSERT INTO "order" (customer_id, created_at, total) VALUES (2, 1325502201, 40.0);
