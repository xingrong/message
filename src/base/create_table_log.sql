-- Copyright 2012 Jike Inc. All Rights Reserved.
-- Author: xingrong@jike.com (Xing Rong)

CREATE TABLE log 
(
    id              int not null primary key AUTO_INCREMENT,
    time            timestamp DEFAULT CURRENT_TIMESTAMP,
    center_ip       varchar(50) not null,
    service         varchar(10) not null,
    level           varchar(10) not null,
    param           text,
    log             text not null,
) TYPE = MYISAM CHARACTER SET utf8;
