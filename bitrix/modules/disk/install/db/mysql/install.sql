CREATE TABLE b_disk_storage
(
	ID int(11) not null auto_increment,
	NAME varchar(100),
	CODE varchar(32),
	XML_ID varchar(50),

	MODULE_ID varchar(32) not null,
	ENTITY_TYPE varchar(100) not null,
	ENTITY_ID varchar(32) not null,

	ENTITY_MISC_DATA text,
	ROOT_OBJECT_ID int(11),
	USE_INTERNAL_RIGHTS tinyint(1),

	SITE_ID CHAR(2),

	PRIMARY KEY (ID),

	UNIQUE KEY IX_DISK_PH_1 (MODULE_ID, ENTITY_TYPE, ENTITY_ID),
	KEY IX_DISK_PH_2 (XML_ID)
);

CREATE TABLE b_disk_object
(
	ID int(11) not null auto_increment,
	NAME varchar(255) not null DEFAULT '',
	TYPE int(11) not null,
	CODE varchar(50),
	XML_ID varchar(50),
	STORAGE_ID int(11) not null,
	REAL_OBJECT_ID int(11),
	PARENT_ID int(11),
	CONTENT_PROVIDER varchar(10),

	CREATE_TIME datetime not null,
	UPDATE_TIME datetime,
	DELETE_TIME datetime,

	CREATED_BY int(11),
	UPDATED_BY int(11),
	DELETED_BY int(11) DEFAULT 0,

	GLOBAL_CONTENT_VERSION int(11),
	FILE_ID int(11),
	TYPE_FILE int(11),
	SIZE bigint,
	EXTERNAL_HASH varchar(255),
	DELETED_TYPE int(11) DEFAULT 0,

	PRIMARY KEY (ID),

	KEY IX_DISK_O_1 (REAL_OBJECT_ID),
	KEY IX_DISK_O_2 (PARENT_ID, DELETED_TYPE, TYPE),
	KEY IX_DISK_O_3 (STORAGE_ID, CODE),
	KEY IX_DISK_O_4 (STORAGE_ID, DELETED_TYPE),
	UNIQUE KEY IX_DISK_O_5 (NAME, PARENT_ID),
	KEY IX_DISK_O_6 (STORAGE_ID, XML_ID),
	KEY IX_DISK_O_7 (UPDATE_TIME)
);

CREATE TABLE b_disk_object_path
(
	ID int(11) not null auto_increment,
	PARENT_ID int(11) not null,
	OBJECT_ID int(11) not null,
	DEPTH_LEVEL int(11),
	PRIMARY KEY (ID),

	UNIQUE KEY IX_DISK_OP_1 (PARENT_ID, DEPTH_LEVEL, OBJECT_ID),
	UNIQUE KEY IX_DISK_OP_2 (OBJECT_ID, PARENT_ID, DEPTH_LEVEL)
);

CREATE TABLE b_disk_version
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11) not null,
	FILE_ID int(11) not null,
	SIZE bigint,
	NAME varchar(255),

	CREATE_TIME datetime not null,
	CREATED_BY int(11),

	OBJECT_CREATE_TIME datetime,
	OBJECT_CREATED_BY int(11),
	OBJECT_UPDATE_TIME datetime,
	OBJECT_UPDATED_BY int(11),
	GLOBAL_CONTENT_VERSION int(11),

	MISC_DATA text,

	PRIMARY KEY (ID),

	KEY IX_DISK_V_1 (OBJECT_ID)
);

CREATE TABLE b_disk_right
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11) not null,
	TASK_ID int(11) not null,
	ACCESS_CODE varchar(50) not null,
	DOMAIN varchar(50),
	NEGATIVE tinyint(1) not null DEFAULT 0,

	PRIMARY KEY (ID),

	KEY IX_DISK_R_1 (OBJECT_ID, NEGATIVE),
	KEY IX_DISK_R_2 (ACCESS_CODE, TASK_ID)
);

CREATE TABLE b_disk_simple_right
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11) not null,
	ACCESS_CODE varchar(50) not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_SR_1 (OBJECT_ID),
	KEY IX_DISK_SR_2 (ACCESS_CODE)
);

CREATE TABLE b_disk_attached_object
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11) not null,
	VERSION_ID int(11),
	IS_EDITABLE tinyint(1) not null DEFAULT 0,
	ALLOW_EDIT tinyint(1) not null DEFAULT 0,

	MODULE_ID varchar(32) not null,
	ENTITY_TYPE varchar(100) not null,
	ENTITY_ID int(11) not null,

	CREATE_TIME datetime not null,
	CREATED_BY int(11),

	PRIMARY KEY (ID),

	KEY IX_DISK_AO_1 (OBJECT_ID, VERSION_ID),
	KEY IX_DISK_AO_2 (MODULE_ID, ENTITY_TYPE, ENTITY_ID)
);

CREATE TABLE b_disk_external_link
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11) not null,
	VERSION_ID int(11),
	HASH varchar(32) not null,
	PASSWORD varchar(32),
	SALT varchar(32),
	DEATH_TIME datetime,
	DESCRIPTION text,
	DOWNLOAD_COUNT int(11),
	TYPE int(11),

	CREATE_TIME datetime not null,
	CREATED_BY int(11),

	PRIMARY KEY (ID),

	KEY IX_DISK_EL_1 (OBJECT_ID),
	KEY IX_DISK_EL_2 (HASH),
	KEY IX_DISK_EL_3 (CREATED_BY)
);

CREATE TABLE b_disk_sharing
(
	ID int(11) not null auto_increment,
  PARENT_ID int(11),
  CREATED_BY int(11),

  FROM_ENTITY VARCHAR(50) not null,
	TO_ENTITY VARCHAR(50) not null,

	LINK_STORAGE_ID int(11),
	LINK_OBJECT_ID int(11),

	REAL_OBJECT_ID int(11) not null,
	REAL_STORAGE_ID int(11) not null,

	DESCRIPTION text,
	CAN_FORWARD tinyint(1),
	STATUS int(11) not null,
	TYPE int(11) not null,

	TASK_NAME VARCHAR(50),
	IS_EDITABLE tinyint(1) not null DEFAULT 0,

	PRIMARY KEY (ID),

	KEY IX_DISK_S_1 (REAL_STORAGE_ID, REAL_OBJECT_ID),
	KEY IX_DISK_S_2 (FROM_ENTITY),
	KEY IX_DISK_S_3 (TO_ENTITY),
	KEY IX_DISK_S_4 (LINK_STORAGE_ID, LINK_OBJECT_ID),
	KEY IX_DISK_S_5 (TYPE, PARENT_ID)
);

CREATE TABLE b_disk_edit_session
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11),
	VERSION_ID int(11),
	USER_ID int(11) not null,
	OWNER_ID int(11) not null,
	IS_EXCLUSIVE tinyint(1),
	SERVICE VARCHAR(10) not null,
	SERVICE_FILE_ID VARCHAR(255) not null,
	SERVICE_FILE_LINK text not null,
	CREATE_TIME datetime not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_ES_1 (OBJECT_ID, VERSION_ID),
	KEY IX_DISK_ES_2 (USER_ID)
);

CREATE TABLE b_disk_tmp_file
(
	ID int(11) not null auto_increment,
	TOKEN VARCHAR(32) not null,
	FILENAME VARCHAR(255),
	PATH VARCHAR(255),
	BUCKET_ID int(11),
	SIZE bigint,
	WIDTH int(11),
	HEIGHT int(11),
	IS_CLOUD tinyint(1),
	CREATED_BY int(11),
	CREATE_TIME datetime not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_TF_1 (TOKEN)
);

CREATE TABLE b_disk_deleted_log
(
	ID int(11) not null auto_increment,
	USER_ID int(11) not null,
	STORAGE_ID int(11) not null,
	OBJECT_ID int(11) not null,
	TYPE int(11) not null,
	CREATE_TIME datetime not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_DL_1 (STORAGE_ID, CREATE_TIME),
	KEY IX_DISK_DL_2 (OBJECT_ID)
);

CREATE TABLE b_disk_cloud_import
(
	ID int(11) not null auto_increment,
	OBJECT_ID int(11),
	VERSION_ID int(11),
	TMP_FILE_ID int(11),
	DOWNLOADED_CONTENT_SIZE bigint DEFAULT 0,
	CONTENT_SIZE bigint DEFAULT 0,
	CONTENT_URL text,
	MIME_TYPE VARCHAR(255),
	USER_ID int(11) not null,
	SERVICE VARCHAR(10) not null,
	SERVICE_OBJECT_ID text not null,
	ETAG VARCHAR(255),
	CREATE_TIME datetime not null,

	PRIMARY KEY (ID),

	KEY IX_DISK_CI_1 (OBJECT_ID, VERSION_ID),
	KEY IX_DISK_CI_2 (TMP_FILE_ID)
);