CREATE TABLE users (
     id MEDIUMINT NOT NULL AUTO_INCREMENT,
     hash CHAR(100) NOT NULL,
     deps INT(10) NOT NULL,
     regs INT(10) NOT NULL,
     click INT(10) NOT NULL,
     package TEXT(160),
     PRIMARY KEY(id),
     CONSTRAINT  users_unique UNIQUE (hash)
);


// --- don't use that: 
ALTER TABLE users
  ADD regs INT(10) NOT NULL
    AFTER deps;

ALTER TABLE users
  ADD click INT(10) NOT NULL
    AFTER regs;


