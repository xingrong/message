CREATE TABLE smsinfo 
(
    id              int not null primary key AUTO_INCREMENT,
    time            timestamp DEFAULT CURRENT_TIMESTAMP,
    username        varchar(100),
    phone           varchar(100) not null,
    content         text not null,
    user_ip         varchar(50),
    feedback        varchar(100),
    priority        int DEFAULT 2,
    filter          int DEFAULT 0,
    is_sent         int DEFAULT 0,
    repeat_num      int DEFAULT 0
) TYPE = MYISAM CHARACTER SET utf8;
