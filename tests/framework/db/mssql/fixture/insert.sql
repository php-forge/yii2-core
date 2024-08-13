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

CREATE TABLE [dbo].[order_item] (
    [order_id] [int] NOT NULL,
    [item_id] [int] NOT NULL,
    [quantity] [int] NOT NULL,
    [subtotal] [decimal](10,0) NOT NULL,
    CONSTRAINT [PK_order_item] PRIMARY KEY CLUSTERED (
        [order_id] ASC,
        [item_id] ASC
    ) ON [PRIMARY]
);

CREATE TABLE [dbo].[type] (
    [int_col] [int] NOT NULL,
    [int_col2] [int] DEFAULT '1',
    [tinyint_col] [tinyint] DEFAULT '1',
    [smallint_col] [smallint] DEFAULT '1',
    [char_col] [char](100) NOT NULL,
    [char_col2] [varchar](100) DEFAULT 'something',
    [char_col3] [text],
    [float_col] [decimal](4,3) NOT NULL,
    [float_col2] [float] DEFAULT '1.23',
    [blob_col] [varbinary](MAX),
    [numeric_col] [decimal](5,2) DEFAULT '33.22',
    [time] [datetime] NOT NULL DEFAULT '2002-01-01 00:00:00',
    [bool_col] [tinyint] NOT NULL,
    [bool_col2] [tinyint] DEFAULT '1'
);

CREATE TABLE [dbo].[null_values] (
  [id] [int] IDENTITY NOT NULL,
  var1 [int] NULL,
  var2 [int] NULL,
  var3 [int] DEFAULT NULL,
  stringcol [varchar](32) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE [dbo].[negative_default_values] (
  [smallint_col] [smallint] DEFAULT -123,
  [int_col] [int] DEFAULT -123,
  [bigint_col] [bigint] DEFAULT -123,
  [float_col] [float] DEFAULT -12345.6789,
  [numeric_col] [decimal](5,2) DEFAULT -33.22
);

CREATE TABLE [T_constraints_2]
(
    [C_id_1] INT NOT NULL,
    [C_id_2] INT NOT NULL,
    [C_index_1] INT NULL,
    [C_index_2_1] INT NULL,
    [C_index_2_2] INT NULL,
    CONSTRAINT [CN_constraints_2_multi] UNIQUE ([C_index_2_1], [C_index_2_2]),
    CONSTRAINT [CN_pk] PRIMARY KEY ([C_id_1], [C_id_2])
);

INSERT INTO [dbo].[customer] ([email], [name], [address], [status], [profile_id]) VALUES ('user1@example.com', 'user1', 'address1', 1, 1);
INSERT INTO [dbo].[customer] ([email], [name], [address], [status]) VALUES ('user2@example.com', 'user2', 'address2', 1);
INSERT INTO [dbo].[customer] ([email], [name], [address], [status], [profile_id]) VALUES ('user3@example.com', 'user3', 'address3', 2, 2);

INSERT INTO [dbo].[order_item] ([order_id], [item_id], [quantity], [subtotal]) VALUES (1, 1, 1, 30.0);
INSERT INTO [dbo].[order_item] ([order_id], [item_id], [quantity], [subtotal]) VALUES (1, 2, 2, 40.0);
INSERT INTO [dbo].[order_item] ([order_id], [item_id], [quantity], [subtotal]) VALUES (2, 4, 1, 10.0);
INSERT INTO [dbo].[order_item] ([order_id], [item_id], [quantity], [subtotal]) VALUES (2, 5, 1, 15.0);
INSERT INTO [dbo].[order_item] ([order_id], [item_id], [quantity], [subtotal]) VALUES (2, 3, 1, 8.0);
INSERT INTO [dbo].[order_item] ([order_id], [item_id], [quantity], [subtotal]) VALUES (3, 2, 1, 40.0);
