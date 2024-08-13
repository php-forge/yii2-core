IF OBJECT_ID('[dbo].[composite_fk]', 'U') IS NOT NULL DROP TABLE [dbo].[composite_fk];
IF OBJECT_ID('[dbo].[order_item]', 'U') IS NOT NULL DROP TABLE [dbo].[order_item];
IF OBJECT_ID('[dbo].[order_item_with_null_fk]', 'U') IS NOT NULL DROP TABLE [dbo].[order_item_with_null_fk];
IF OBJECT_ID('[dbo].[item]', 'U') IS NOT NULL DROP TABLE [dbo].[item];
IF OBJECT_ID('[dbo].[order]', 'U') IS NOT NULL DROP TABLE [dbo].[order];
IF OBJECT_ID('[dbo].[order_with_null_fk]', 'U') IS NOT NULL DROP TABLE [dbo].[order_with_null_fk];
IF OBJECT_ID('[dbo].[category]', 'U') IS NOT NULL DROP TABLE [dbo].[category];
IF OBJECT_ID('[dbo].[customer]', 'U') IS NOT NULL DROP TABLE [dbo].[customer];
IF OBJECT_ID('[dbo].[profile]', 'U') IS NOT NULL DROP TABLE [dbo].[profile];
IF OBJECT_ID('[dbo].[type]', 'U') IS NOT NULL DROP TABLE [dbo].[type];
IF OBJECT_ID('[dbo].[null_values]', 'U') IS NOT NULL DROP TABLE [dbo].[null_values];
IF OBJECT_ID('[dbo].[test_trigger]', 'U') IS NOT NULL DROP TABLE [dbo].[test_trigger];
IF OBJECT_ID('[dbo].[test_trigger_alert]', 'U') IS NOT NULL DROP TABLE [dbo].[test_trigger_alert];
IF OBJECT_ID('[dbo].[negative_default_values]', 'U') IS NOT NULL DROP TABLE [dbo].[negative_default_values];
IF OBJECT_ID('[dbo].[animal]', 'U') IS NOT NULL DROP TABLE [dbo].[animal];
IF OBJECT_ID('[dbo].[default_pk]', 'U') IS NOT NULL DROP TABLE [dbo].[default_pk];
IF OBJECT_ID('[dbo].[document]', 'U') IS NOT NULL DROP TABLE [dbo].[document];
IF OBJECT_ID('[dbo].[dossier]', 'U') IS NOT NULL DROP TABLE [dbo].[dossier];
IF OBJECT_ID('[dbo].[employee]', 'U') IS NOT NULL DROP TABLE [dbo].[employee];
IF OBJECT_ID('[dbo].[department]', 'U') IS NOT NULL DROP TABLE [dbo].[department];
IF OBJECT_ID('[dbo].[animal_view]', 'V') IS NOT NULL DROP VIEW [dbo].[animal_view];
IF OBJECT_ID('[T_constraints_4]', 'U') IS NOT NULL DROP TABLE [T_constraints_4];
IF OBJECT_ID('[T_constraints_3]', 'U') IS NOT NULL DROP TABLE [T_constraints_3];
IF OBJECT_ID('[T_constraints_2]', 'U') IS NOT NULL DROP TABLE [T_constraints_2];
IF OBJECT_ID('[T_constraints_1]', 'U') IS NOT NULL DROP TABLE [T_constraints_1];
IF OBJECT_ID('[T_upsert]', 'U') IS NOT NULL DROP TABLE [T_upsert];
IF OBJECT_ID('[T_upsert_1]', 'U') IS NOT NULL DROP TABLE [T_upsert_1];
IF OBJECT_ID('[T_upsert_varbinary]', 'U') IS NOT NULL DROP TABLE [T_upsert_varbinary];
IF OBJECT_ID('[table.with.special.characters]', 'U') IS NOT NULL DROP TABLE [table.with.special.characters];
IF OBJECT_ID('[stranger ''table]', 'U') IS NOT NULL DROP TABLE [stranger 'table];
IF OBJECT_ID('[foo1]', 'U') IS NOT NULL DROP TABLE [foo1];

CREATE TABLE [T_upsert]
(
    [id] INT NOT NULL IDENTITY PRIMARY KEY,
    [ts] INT NULL,
    [email] VARCHAR(128) NOT NULL UNIQUE,
    [recovery_email] VARCHAR(128) NULL,
    [address] TEXT NULL,
    [status] TINYINT NOT NULL DEFAULT 0,
    [orders] INT NOT NULL DEFAULT 0,
    [profile_id] INT NULL,
    UNIQUE ([email], [recovery_email])
);

CREATE TABLE [T_upsert_1]
(
    [a] INT NOT NULL,
    UNIQUE ([a])
);

CREATE TABLE [T_upsert_varbinary]
(
    [id] INT NOT NULL,
    [blob_col] [varbinary](MAX),
    UNIQUE ([id])
);

CREATE TABLE [dbo].[animal] (
    [id] [int] IDENTITY NOT NULL,
    [type] [varchar](255) NOT NULL,
    CONSTRAINT [PK_animal] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE VIEW [dbo].[animal_view] AS SELECT * FROM [dbo].[animal];

CREATE TABLE [dbo].[customer] (
    [id] [int] IDENTITY NOT NULL,
    [email] [varchar](128) NOT NULL,
    [name] [varchar](128),
    [address] [text],
    [status] [int] DEFAULT 0,
    [profile_id] [int],
    CONSTRAINT [PK_customer] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[order] (
    [id] [int] IDENTITY NOT NULL,
    [customer_id] [int] NOT NULL,
    [created_at] [int] NOT NULL,
    [total] [decimal](10,0) NOT NULL,
    CONSTRAINT [PK_order] PRIMARY KEY CLUSTERED (
        [id] ASC
    ) ON [PRIMARY]
);

INSERT INTO [dbo].[animal] (type) VALUES ('yiiunit\data\ar\Cat');
INSERT INTO [dbo].[animal] (type) VALUES ('yiiunit\data\ar\Dog');

INSERT INTO [dbo].[customer] ([email], [name], [address], [status], [profile_id]) VALUES ('user1@example.com', 'user1', 'address1', 1, 1);
INSERT INTO [dbo].[customer] ([email], [name], [address], [status]) VALUES ('user2@example.com', 'user2', 'address2', 1);
INSERT INTO [dbo].[customer] ([email], [name], [address], [status], [profile_id]) VALUES ('user3@example.com', 'user3', 'address3', 2, 2);

INSERT INTO [dbo].[order] ([customer_id], [created_at], [total]) VALUES (1, 1325282384, 110.0);
INSERT INTO [dbo].[order] ([customer_id], [created_at], [total]) VALUES (2, 1325334482, 33.0);
INSERT INTO [dbo].[order] ([customer_id], [created_at], [total]) VALUES (2, 1325502201, 40.0);
