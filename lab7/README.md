# Lab 7 - Database Archive Backend

This lab implements a PHP backend to archive course and student data from JSON files into MySQL database tables.

## Database Schema

The database contains two tables:

### `courses` Table
- `crn` (INT(11), PRIMARY KEY) - Course Reference Number
- `prefix` (VARCHAR(4), NOT NULL) - Course prefix (e.g., "ITWS")
- `number` (SMALLINT(4), NOT NULL) - Course number
- `title` (VARCHAR(255), NOT NULL) - Course title

### `students` Table
- `RIN` (INT(9), PRIMARY KEY) - Rensselaer Identification Number
- `RCSID` (CHAR(7)) - Rensselaer Computing System ID (optional)
- `first_name` (VARCHAR(100), NOT NULL) - Student's first name
- `last_name` (VARCHAR(100), NOT NULL) - Student's last name
- `alias` (VARCHAR(100), NOT NULL) - Student's alias
- `phone` (INT(10)) - Phone number (optional)

## Setup

1. **Create the database tables:**
   - Execute the SQL commands in `schema.sql` in your MySQL database
   - You can do this via phpMyAdmin, MySQL command line, or by including the schema in your PHP setup script

2. **Configure database connection:**
   - Set up your database connection in your PHP files
   - The provided PHP files assume you have a `$conn` mysqli connection object available

## Files

### `schema.sql`
Contains the SQL schema to create the `courses` and `students` tables. Execute this file to set up your database structure.

### `index.php`
Main web page for archiving course data. Provides a form to input a JSON file path and archive the data into the database.

**To use:**
1. Include your database connection file at the top of `index.php`
2. Access the page via your web server
3. Enter the path to your JSON file and click "Archive Data"

### `archive.php`
Contains the `archiveCourses()` function that reads a JSON file and inserts the data into the database tables.

**Function signature:**
```php
function archiveCourses($conn, $jsonFile)
```

**Returns:**
- `["success" => true, "inserted" => count, "errors" => []]` on success
- `["success" => false, "message" => "error message"]` on failure

**Expected JSON Format:**
```json
{
    "courses": [
        {
            "crn": 12345,
            "prefix": "ITWS",
            "number": 2110,
            "title": "Web Systems Development"
        }
    ],
    "students": [
        {
            "RIN": 123456789,
            "RCSID": "doej",
            "first_name": "John",
            "last_name": "Doe",
            "alias": "jdoe",
            "phone": 5185551234
        }
    ]
}
```

### `reset.php`
A web page with buttons to delete or reset the database tables. Useful for testing and grading.

**To use:**
1. Include your database connection file at the top of `reset.php`
2. Access the page via your web server
3. Use the buttons to delete or reset tables (with confirmation dialogs)

## Notes

- The archive function uses `ON DUPLICATE KEY UPDATE` to handle duplicate entries (updates existing records)
- All files assume you have a mysqli connection object `$conn` available
- Make sure to configure your database connection settings according to your environment
- The reset page includes confirmation dialogs to prevent accidental data loss

# LAB PART 2
```sql
CREATE TABLE IF NOT EXISTS courses (
    crn INT(11) PRIMARY KEY,
    prefix VARCHAR(4) NOT NULL,
    number SMALLINT(4) NOT NULL,
    title VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS students (
    RIN INT(9) PRIMARY KEY,
    RCSID CHAR(7),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    alias VARCHAR(100) NOT NULL,
    phone INT(10)
);


-- 1. Add address fields to students table
ALTER TABLE students 
ADD COLUMN street VARCHAR(255),
ADD COLUMN city VARCHAR(100),
ADD COLUMN state VARCHAR(2),
ADD COLUMN zip VARCHAR(10);

-- 2. Add section and year fields to courses table
ALTER TABLE courses 
ADD COLUMN section VARCHAR(10),
ADD COLUMN year INT(4);

-- 3. Create grades table
CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crn INT(11) NOT NULL,
    RIN INT(9) NOT NULL,
    grade INT(3) NOT NULL,
    FOREIGN KEY (crn) REFERENCES courses(crn),
    FOREIGN KEY (RIN) REFERENCES students(RIN)
);

-- 4. Insert at least 4 courses
INSERT INTO courses (crn, prefix, number, title, section, year) VALUES
(12345, 'ITWS', 2110, 'Web Systems Development', '01', 2025),
(12346, 'ITWS', 1100, 'Introduction to IT and Web Science', '01', 2025),
(12347, 'CSCI', 1200, 'Data Structures', '01', 2025),
(12348, 'ITWS', 4310, 'Managing IT Resources', '01', 2025);

-- 5. Insert at least 4 students
INSERT INTO students (RIN, RCSID, first_name, last_name, alias, phone, street, city, state, zip) VALUES
(123456789, 'doej', 'John', 'Doe', 'jdoe', 5185551234, '123 Main St', 'Troy', 'NY', '12180'),
(234567890, 'smithj', 'Jane', 'Smith', 'jsmith', 5185552345, '456 Oak Ave', 'Albany', 'NY', '12201'),
(345678901, 'brownm', 'Mike', 'Brown', 'mbrown', 5185553456, '789 Pine Rd', 'Schenectady', 'NY', '12345'),
(456789012, 'wilsons', 'Sarah', 'Wilson', 'swilson', 5185554567, '321 Elm St', 'Troy', 'NY', '12180');

-- 6. Add 10 grades
INSERT INTO grades (crn, RIN, grade) VALUES
(12345, 123456789, 95),
(12345, 234567890, 88),
(12345, 345678901, 92),
(12346, 123456789, 87),
(12346, 234567890, 91),
(12347, 345678901, 89),
(12347, 456789012, 94),
(12348, 123456789, 90),
(12348, 234567890, 85),
(12348, 456789012, 93);

-- 7. List all students in lexicographical order by RIN, last name, RCSID, and first name
SELECT * FROM students 
ORDER BY RIN, last_name, RCSID, first_name;

-- 8. List all students RIN, name, and address if their grade in any course was higher than 90
SELECT DISTINCT s.RIN, s.first_name, s.last_name, s.street, s.city, s.state, s.zip
FROM students s
INNER JOIN grades g ON s.RIN = g.RIN
WHERE g.grade > 90
ORDER BY s.RIN;

-- 9. List out the average grade in each course
SELECT c.crn, c.prefix, c.number, c.title, AVG(g.grade) AS average_grade
FROM courses c
LEFT JOIN grades g ON c.crn = g.crn
GROUP BY c.crn, c.prefix, c.number, c.title
ORDER BY c.crn;

-- 10. List out the number of students in each course
SELECT c.crn, c.prefix, c.number, c.title, COUNT(DISTINCT g.RIN) AS student_count
FROM courses c
LEFT JOIN grades g ON c.crn = g.crn
GROUP BY c.crn, c.prefix, c.number, c.title
ORDER BY c.crn;

```