CREATE TABLE ufeed (
    id int(11) NOT NULL,
    title varchar(1024) NOT NULL,
    url varchar(512) NOT NULL,
    status varchar(10) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
