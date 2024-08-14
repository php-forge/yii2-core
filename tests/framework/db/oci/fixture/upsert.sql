BEGIN EXECUTE IMMEDIATE 'DROP TABLE "composite_fk"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "order_item"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "item"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "order_item_with_null_fk"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "order"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "order_with_null_fk"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "category"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "customer"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "profile"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "type"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "null_values"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "negative_default_values"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "constraints"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "bool_values"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "animal"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "default_pk"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "default_multiple_pk"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "document"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "dossier"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "employee"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "department"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP VIEW "animal_view"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "validator_main"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "validator_ref"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "bit_values"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END; --
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "T_constraints_4"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "T_constraints_3"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "T_constraints_2"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "T_constraints_1"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "T_upsert"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "T_upsert_1"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP TABLE "T_upsert_varbinary"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;--

BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "profile_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "customer_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "category_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "item_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "order_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "order_with_null_fk_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "null_values_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "bool_values_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "animal_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "document_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "T_upsert_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "department_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--
BEGIN EXECUTE IMMEDIATE 'DROP SEQUENCE "employee_SEQ"'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -2289 THEN RAISE; END IF; END;--

/* STATEMENTS */

CREATE TABLE "T_upsert"
(
    "id" INT NOT NULL PRIMARY KEY,
    "ts" INT NULL,
    "email" VARCHAR(128) NOT NULL UNIQUE,
    "recovery_email" VARCHAR(128) NULL,
    "address" CLOB NULL,
    "status" NUMBER(5,0) DEFAULT 0 NOT NULL,
    "orders" INT DEFAULT 0 NOT NULL,
    "profile_id" INT NULL,
    CONSTRAINT "CN_T_upsert_multi" UNIQUE ("email", "recovery_email")
);
CREATE SEQUENCE "T_upsert_SEQ";

CREATE TABLE "T_upsert_1"
(
    "a" INT NOT NULL PRIMARY KEY
);

CREATE TABLE "T_upsert_varbinary"
(
    "id" integer not null,
    "blob_col" blob,
    CONSTRAINT "T_upsert_varbinary_PK" PRIMARY KEY ("id") ENABLE
);

CREATE TABLE "animal" (
  "id" integer,
  "type" varchar2(255) not null,
  CONSTRAINT "animal_PK" PRIMARY KEY ("id") ENABLE
);
CREATE SEQUENCE "animal_SEQ";

CREATE TABLE "customer" (
  "id" integer not null,
  "email" varchar2(128) NOT NULL UNIQUE,
  "name" varchar2(128),
  "address" varchar(4000),
  "status" integer DEFAULT 0,
  "bool_status" char DEFAULT 0 check ("bool_status" in (0,1)),
  "profile_id" integer,
  CONSTRAINT "customer_PK" PRIMARY KEY ("id") ENABLE
);
CREATE SEQUENCE "customer_SEQ";

comment on column "customer"."email" is 'someone@example.com';

CREATE TABLE "order" (
  "id" integer not null,
  "customer_id" integer NOT NULL references "customer"("id") on DELETE CASCADE,
  "created_at" integer NOT NULL,
  "total" decimal(10,0) NOT NULL,
  CONSTRAINT "order_PK" PRIMARY KEY ("id") ENABLE
);
CREATE SEQUENCE "order_SEQ";

/* TRIGGERS */

CREATE TRIGGER "T_upsert_TRG" BEFORE INSERT ON "T_upsert" FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
    IF INSERTING AND :NEW."id" IS NULL THEN SELECT "T_upsert_SEQ".NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
END COLUMN_SEQUENCES;
END;
/
CREATE TRIGGER "animal_TRG" BEFORE INSERT ON "animal" FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
  IF INSERTING AND :NEW."id" IS NULL THEN SELECT "animal_SEQ".NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
END COLUMN_SEQUENCES;
END;
/
CREATE TRIGGER "customer_TRG" BEFORE INSERT ON "customer" FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
  IF INSERTING AND :NEW."id" IS NULL THEN SELECT "customer_SEQ".NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
END COLUMN_SEQUENCES;
END;
/
CREATE TRIGGER "order_TRG" BEFORE INSERT ON "order" FOR EACH ROW BEGIN <<COLUMN_SEQUENCES>> BEGIN
  IF INSERTING AND :NEW."id" IS NULL THEN SELECT "order_SEQ".NEXTVAL INTO :NEW."id" FROM SYS.DUAL; END IF;
END COLUMN_SEQUENCES;
END;
/

/* TRIGGERS */

INSERT INTO "animal" ("type") VALUES ('yiiunit\data\ar\Cat');
INSERT INTO "animal" ("type") VALUES ('yiiunit\data\ar\Dog');

INSERT INTO "customer" ("email", "name", "address", "status", "bool_status", "profile_id") VALUES ('user1@example.com', 'user1', 'address1', 1, 1, 1);
INSERT INTO "customer" ("email", "name", "address", "status", "bool_status") VALUES ('user2@example.com', 'user2', 'address2', 1, 1);
INSERT INTO "customer" ("email", "name", "address", "status", "bool_status", "profile_id") VALUES ('user3@example.com', 'user3', 'address3', 2, 0, 2);

INSERT INTO "order" ("customer_id", "created_at", "total") VALUES (1, 1325282384, 110.0);
INSERT INTO "order" ("customer_id", "created_at", "total") VALUES (2, 1325334482, 33.0);
INSERT INTO "order" ("customer_id", "created_at", "total") VALUES (2, 1325502201, 40.0);
