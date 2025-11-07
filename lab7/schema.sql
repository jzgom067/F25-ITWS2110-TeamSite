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

CREATE TABLE IF NOT EXISTS archive (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(20) NOT NULL,
    item_key VARCHAR(100) NOT NULL,
    title VARCHAR(255),
    description TEXT,
    material TEXT,
    data JSON,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_archive (type, item_key)
);

