-- Database schema for courses and students tables

CREATE TABLE IF NOT EXISTS courses (
    crn INT(11) PRIMARY KEY,
    prefix VARCHAR(4) NOT NULL,
    number SMALLINT(4) NOT NULL,
    title VARCHAR(255) NOT NULL,
    course_content JSON
);

CREATE TABLE IF NOT EXISTS students (
    RIN INT(9) PRIMARY KEY,
    RCSID CHAR(7),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    alias VARCHAR(100) NOT NULL,
    phone INT(10)
);

