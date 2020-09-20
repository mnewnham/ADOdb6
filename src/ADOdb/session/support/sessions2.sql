
CREATE Table SCripts

Oracle
======

CREATE TABLE SESSIONS2
(
  SESSKEY    VARCHAR2(48 BYTE)                  NOT NULL,
  EXPIRY     DATE                               NOT NULL,
  EXPIREREF  VARCHAR2(200 BYTE),
  CREATED    DATE                               NOT NULL,
  MODIFIED   DATE                               NOT NULL,
  SESSDATA   CLOB,
  PRIMARY KEY(SESSKEY)
);


CREATE INDEX SESS2_EXPIRY ON SESSIONS2(EXPIRY);
CREATE UNIQUE INDEX SESS2_PK ON SESSIONS2(SESSKEY);
CREATE INDEX SESS2_EXP_REF ON SESSIONS2(EXPIREREF);



 MySQL
 =====

CREATE TABLE sessions2(
	sesskey VARCHAR( 64 ) NOT NULL DEFAULT '',
	expiry TIMESTAMP NOT NULL ,
	expireref VARCHAR( 250 ) DEFAULT '',
	created TIMESTAMP NOT NULL ,
	modified TIMESTAMP NOT NULL ,
	sessdata CLOB,
	PRIMARY KEY ( sesskey ) ,
	INDEX sess2_expiry( expiry ),
	INDEX sess2_expireref( expireref )
)

