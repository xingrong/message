CREATE TABLE monitor 
(
    id              int not null primary key AUTO_INCREMENT,
    time            timestamp DEFAULT CURRENT_TIMESTAMP,
    center_ip       varchar(50) not null,
    error           varchar(100) not null,
    errorMsg        text not null,
) TYPE = MYISAM CHARACTER SET utf8;
