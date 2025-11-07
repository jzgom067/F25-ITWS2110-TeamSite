<?php
// Main page - auto-imports course content from JSON and displays it
require_once 'db_connect.php';
require_once 'archive.php';

// Function to drop all tables
function dropTables($conn) {
    $tables = ['grades', 'courses', 'students', 'archive'];
    $dropped = [];
    $errors = [];
    
    try {
        foreach ($tables as $table) {
            $sql = "DROP TABLE IF EXISTS $table";
            if ($conn->query($sql) === TRUE) {
                $dropped[] = $table;
            } else {
                $errors[] = "Error dropping table $table: " . $conn->error;
            }
        }
    } catch (Exception $e) {
        $errors[] = "Exception dropping tables: " . $e->getMessage();
    }
    
    return [
        "success" => empty($errors),
        "dropped" => $dropped,
        "errors" => $errors
    ];
}

// Function to recreate tables from schema
function recreateTables($conn) {
    $errors = [];
    
    try {
        // Read and execute schema.sql
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        $queries = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($queries as $query) {
            if (!empty($query)) {
                if ($conn->query($query) === FALSE) {
                    $errors[] = "Error executing schema query: " . $conn->error;
                }
            }
        }
        
        // Read and execute init.sql if it exists
        if (file_exists(__DIR__ . '/init.sql')) {
            $init = file_get_contents(__DIR__ . '/init.sql');
            $initQueries = array_filter(array_map('trim', explode(';', $init)));
            
            foreach ($initQueries as $query) {
                if (!empty($query)) {
                    if ($conn->query($query) === FALSE) {
                        $errors[] = "Error executing init query: " . $conn->error;
                    }
                }
            }
        }
    } catch (Exception $e) {
        $errors[] = "Exception recreating tables: " . $e->getMessage();
    }
    
    return [
        "success" => empty($errors),
        "errors" => $errors
    ];
}

// Handle reset table action
$resetSuccess = false;
$resetError = '';
$resetDbError = false;
if (isset($_POST['action']) && $_POST['action'] === 'reset' && isset($conn)) {
    try {
        $dropResult = dropTables($conn);
        $createResult = recreateTables($conn);
        if ($createResult['success']) {
            $resetSuccess = true;
        } else {
            $resetError = implode('<br>', $createResult['errors']);
            $resetDbError = true;
        }
    } catch (Exception $e) {
        $resetError = "Exception during reset: " . $e->getMessage();
        $resetDbError = true;
    }
}

// No auto-import - JSON is only loaded when user clicks "Refresh from JSON"
$dbError = false;
$dbErrorMessage = '';

$selectedType = isset($_GET['type']) ? $_GET['type'] : '';
$selectedKey = isset($_GET['key']) ? $_GET['key'] : '';
$syncSuccess = isset($_GET['sync']) && $_GET['sync'] === 'success';
$error = isset($_GET['error']) ? $_GET['error'] : '';
$errorMsg = isset($_GET['msg']) ? $_GET['msg'] : '';
$hasSqlError = $dbError || $resetDbError || ($error === 'sync_failed' && (strpos(strtolower($errorMsg), 'sql') !== false || strpos(strtolower($errorMsg), 'table') !== false || strpos(strtolower($errorMsg), 'database') !== false)) || !empty($resetError);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Content Viewer</title>
    <link rel="stylesheet" href="style.css">
    <script>
        let courseData = null;
        let archivedItems = new Set();
        let selectedType = '<?php echo htmlspecialchars($selectedType); ?>';
        let selectedKey = '<?php echo htmlspecialchars($selectedKey); ?>';

        function loadCourseContent() {
            // First, load archived items to filter them out
            fetch('get_archived_api.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.archived) {
                        result.archived.forEach(item => {
                            archivedItems.add(item.type + ':' + item.item_key);
                        });
                    }
                    // Then load course content from database
                    return fetch('course_content_api.php');
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data && result.data.websys_course && result.data.websys_course.length > 0) {
                        courseData = result.data.websys_course[0];
                        renderNavigation();
                        renderPreview();
                    } else {
                        // No data available - show empty state
                        courseData = null;
                        document.getElementById('contentList').innerHTML = '<li>No course content available. Click "Refresh from JSON" to load content.</li>';
                        document.getElementById('preview').innerHTML = '<p>No course content available. Click "Refresh from JSON" to load content.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading course content:', error);
                    courseData = null;
                    document.getElementById('contentList').innerHTML = '<li>Error loading course content.</li>';
                    document.getElementById('preview').innerHTML = '<p>Error loading course content.</p>';
                });
        }

        function refreshFromJSON() {
            // Submit the form to sync JSON to database
            const form = document.getElementById('refreshForm');
            if (form) {
                form.submit();
            }
        }

        function renderNavigation() {
            if (!courseData) {
                document.getElementById('contentList').innerHTML = '<li>No course content available.</li>';
                return;
            }

            const contentList = document.getElementById('contentList');
            let html = '';
            let hasVisibleItems = false;

            // Render lectures
            if (courseData.lectures && Object.keys(courseData.lectures).length > 0) {
                const lectureKeys = Object.keys(courseData.lectures).sort();
                const visibleLectures = lectureKeys.filter(key => !archivedItems.has('lecture:' + key));
                
                if (visibleLectures.length > 0) {
                    hasVisibleItems = true;
                    html += '<li><strong>Lectures</strong><ul>';
                    visibleLectures.forEach(key => {
                        const lecture = courseData.lectures[key];
                        const isActive = selectedType === 'lecture' && selectedKey === key;
                        html += `<li><a href="?type=lecture&key=${encodeURIComponent(key)}" class="${isActive ? 'active' : ''}">${escapeHtml(lecture.title || key)}</a></li>`;
                    });
                    html += '</ul></li>';
                }
            }

            // Render labs
            if (courseData.labs && Object.keys(courseData.labs).length > 0) {
                const labKeys = Object.keys(courseData.labs).sort();
                const visibleLabs = labKeys.filter(key => !archivedItems.has('lab:' + key));
                
                if (visibleLabs.length > 0) {
                    hasVisibleItems = true;
                    html += '<li><strong>Labs</strong><ul>';
                    visibleLabs.forEach(key => {
                        const lab = courseData.labs[key];
                        const isActive = selectedType === 'lab' && selectedKey === key;
                        html += `<li><a href="?type=lab&key=${encodeURIComponent(key)}" class="${isActive ? 'active' : ''}">${escapeHtml(lab.title || key)}</a></li>`;
                    });
                    html += '</ul></li>';
                }
            }

            contentList.innerHTML = html || '<li>No course content available.</li>';
        }

        function renderPreview() {
            if (!courseData || !selectedType || !selectedKey) {
                document.getElementById('preview').innerHTML = '<p>Select an item from the navigation to view details.</p>';
                return;
            }

            let item = null;
            if (selectedType === 'lecture' && courseData.lectures && courseData.lectures[selectedKey]) {
                item = courseData.lectures[selectedKey];
            } else if (selectedType === 'lab' && courseData.labs && courseData.labs[selectedKey]) {
                item = courseData.labs[selectedKey];
            }

            if (item) {
                let html = '';
                if (item.title) {
                    html += `<h3>${escapeHtml(item.title)}</h3>`;
                }
                if (item.description) {
                    html += `<p><strong>Description:</strong> ${escapeHtml(item.description)}</p>`;
                }
                if (item.material) {
                    html += `<p><strong>Material:</strong> ${escapeHtml(item.material)}</p>`;
                }
                html += `<div style="margin-top: 20px;">
                    <button onclick="archiveItem('${selectedType}', ${JSON.stringify(selectedKey)})">Archive</button>
                </div>`;
                document.getElementById('preview').innerHTML = html;
            } else {
                document.getElementById('preview').innerHTML = '<p>Select an item from the navigation to view details.</p>';
            }
        }

        function archiveItem(type, key) {
            if (!courseData) {
                alert('No course data available');
                return;
            }

            let item = null;
            if (type === 'lecture' && courseData.lectures && courseData.lectures[key]) {
                item = courseData.lectures[key];
            } else if (type === 'lab' && courseData.labs && courseData.labs[key]) {
                item = courseData.labs[key];
            }

            if (!item) {
                alert('Item not found');
                return;
            }

            if (!confirm('Archive this item?')) {
                return;
            }

            // Prepare the data to send
            const archiveData = {
                type: type,
                key: key,
                item: item
            };

            console.log('Archiving item:', archiveData);

            fetch('archive_item_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(archiveData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(result => {
                console.log('Archive response:', result);
                if (result.success) {
                    // Add to archived set FIRST
                    archivedItems.add(type + ':' + key);
                    console.log('Added to archived set:', type + ':' + key);
                    console.log('Current archived items:', Array.from(archivedItems));
                    
                    // Clear preview if this was the selected item
                    if (selectedType === type && selectedKey === key) {
                        selectedType = '';
                        selectedKey = '';
                        // Update URL to remove query parameters
                        const newUrl = window.location.pathname;
                        window.history.replaceState({}, '', newUrl);
                        document.getElementById('preview').innerHTML = '<p>Item archived successfully. Select another item from the navigation.</p>';
                    }
                    
                    // Re-render navigation to immediately remove the item from menu
                    renderNavigation();
                    console.log('Navigation re-rendered after archiving');
                    
                    // Show success message
                    alert('Item archived successfully! It has been removed from the menu.');
                } else {
                    alert('Error archiving item: ' + (result.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error archiving item:', error);
                alert('Error archiving item: ' + error.message + '. Please check the console for details.');
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Load content from database (if it exists)
            // JSON is only imported when user clicks "Refresh from JSON"
            loadCourseContent();
        });
    </script>
</head>
<body>
    <div class="halloween-header">
        <h1>🎃 Spooky Course Content 🎃</h1>
    </div>
    <div style="margin-bottom: 15px;">
        <form method="POST" style="display: inline-block;" onsubmit="return confirm('Reset all database tables? This will drop and recreate all tables, deleting all data!');">
            <input type="hidden" name="action" value="reset">
            <button type="submit">Reset Tables</button>
        </form>
    </div>
    <?php if ($resetSuccess): ?>
        <div class="message success">Successfully reset all tables!</div>
    <?php endif; ?>
    <?php if ($syncSuccess): ?>
        <div class="message success">Successfully synced JSON file to database!</div>
    <?php endif; ?>
    <?php if ($dbError): ?>
        <div class="message error">
            Database Error: <?php echo htmlspecialchars($dbErrorMessage ? $dbErrorMessage : (isset($conn) ? $conn->error : 'Unknown database error')); ?>
            <div style="margin-top: 10px;">
                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Reset all database tables? This will drop and recreate all tables, deleting all data!');">
                    <input type="hidden" name="action" value="reset">
                    <button type="submit">Reset Tables</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($error || $resetError): ?>
        <div class="message error">
            Error: 
            <?php 
            if ($resetError) {
                echo $resetError;
            } elseif ($error === 'db_connection') {
                echo 'Database connection not found';
            } elseif ($error === 'file_not_found') {
                echo 'JSON file not found';
            } elseif ($error === 'json_parse') {
                echo 'Error parsing JSON file';
            } elseif ($error === 'sync_failed') {
                echo htmlspecialchars($errorMsg);
            } else {
                echo 'Unknown error';
            }
            ?>
            <div style="margin-top: 10px;">
                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Reset all database tables? This will drop and recreate all tables, deleting all data!');">
                    <input type="hidden" name="action" value="reset">
                    <button type="submit">Reset Tables</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <div class="container">
        <div class="nav-panel">
            <h2>Course Content</h2>
            <form id="refreshForm" method="POST" action="sync_api.php" style="display: inline-block; margin-bottom: 15px;" onsubmit="return confirm('Refresh content from JSON file? This will update the database.');">
                <button type="submit" name="sync">Refresh from JSON</button>
            </form>
            <ul id="contentList">
                <li>No course content available. Click "Refresh from JSON" to load content.</li>
            </ul>
        </div>
        <div class="preview-panel">
            <div id="preview">
                <p>No course content available. Click "Refresh from JSON" to load content.</p>
            </div>
            </div>
        </div>
</body>
</html>

