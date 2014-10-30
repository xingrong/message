-- Copyright 2012 Jike Inc. All Rights Reserved.
-- Author: xingrong@jike.com (Xing Rong)

CREATE TABLE mailinfo 
(
    id              int not null primary key AUTO_INCREMENT,
    time            timestamp DEFAULT CURRENT_TIMESTAMP,
    mailfrom        varchar(255) not null,
    mailto          varchar(255) not null,
    body            varchar(500) not null,
    user_ip         varchar(50) not null,
    priority        int DEFAULT 2,
    filter          int DEFAULT 0,
    cc              varchar(255),
    bcc             varchar(255),
    subject         varchar(255),
    is_sent         int DEFAULT 0
) TYPE = MYISAM CHARACTER SET utf8;
