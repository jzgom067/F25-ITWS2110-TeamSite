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

