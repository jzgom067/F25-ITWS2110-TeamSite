function confirmDrop() {
    return confirm('Are you sure you want to drop all tables? This cannot be undone!');
}

function confirmRecreate() {
    return confirm('Are you sure you want to drop and recreate all tables? This will delete all data!');
}

function showMessage(message, type) {
    var messageDiv = document.getElementById('message');
    messageDiv.textContent = message;
    messageDiv.className = 'message ' + type;
}

function archiveData() {
    var fileInput = document.getElementById('json_file');
    var file = fileInput.files[0];
    
    if (!file) {
        showMessage('Please select a JSON file.', 'error');
        return;
    }
    
    var reader = new FileReader();
    
    reader.onload = function(e) {
        try {
            var jsonData = JSON.parse(e.target.result);
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'archive_api.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                var msg = 'Successfully archived ' + response.inserted + ' records.';
                                if (response.errors && response.errors.length > 0) {
                                    msg += '\nErrors: ' + response.errors.join('\n');
                                }
                                showMessage(msg, 'success');
                            } else {
                                showMessage('Error: ' + response.message, 'error');
                            }
                        } catch (e) {
                            showMessage('Error parsing response: ' + e.message, 'error');
                        }
                    } else {
                        showMessage('Error: Server returned status ' + xhr.status, 'error');
                    }
                }
            };
            
            xhr.send(JSON.stringify(jsonData));
        } catch (e) {
            showMessage('Error parsing JSON file: ' + e.message, 'error');
        }
    };
    
    reader.onerror = function() {
        showMessage('Error reading file.', 'error');
    };
    
    reader.readAsText(file);
}

document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('archiveForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            archiveData();
        });
    }
    
    // Load course content if on viewer page
    if (document.getElementById('contentList')) {
        refreshContent();
    }
});

function refreshContent() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'content_api.php', true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        buildNavigation(response.courses);
                    } else {
                        showContentError('Error loading content: ' + response.message);
                    }
                } catch (e) {
                    showContentError('Error parsing response: ' + e.message);
                }
            } else {
                showContentError('Error: Server returned status ' + xhr.status);
            }
        }
    };
    
    xhr.send();
}

function buildNavigation(courses) {
    var list = document.getElementById('contentList');
    list.innerHTML = '';
    
    if (!courses || courses.length === 0) {
        list.innerHTML = '<li>No course content available</li>';
        return;
    }
    
    courses.forEach(function(course) {
        var courseItem = document.createElement('li');
        var courseTitle = document.createElement('strong');
        courseTitle.textContent = course.prefix + ' ' + course.number + ': ' + course.title;
        courseItem.appendChild(courseTitle);
        
        var contentList = document.createElement('ul');
        
        if (course.content) {
            if (course.content.lectures && Array.isArray(course.content.lectures)) {
                course.content.lectures.forEach(function(lecture, index) {
                    var item = createContentItem('Lecture ' + (index + 1), lecture, course.crn, 'lecture', index);
                    contentList.appendChild(item);
                });
            }
            
            if (course.content.labs && Array.isArray(course.content.labs)) {
                course.content.labs.forEach(function(lab, index) {
                    var item = createContentItem('Lab ' + (index + 1), lab, course.crn, 'lab', index);
                    contentList.appendChild(item);
                });
            }
        }
        
        if (contentList.children.length > 0) {
            courseItem.appendChild(contentList);
        }
        
        list.appendChild(courseItem);
    });
}

function createContentItem(label, item, crn, type, index) {
    var li = document.createElement('li');
    var link = document.createElement('a');
    link.href = '#';
    link.textContent = label + (item.title ? ': ' + item.title : '');
    link.onclick = function(e) {
        e.preventDefault();
        showPreview(item, label);
    };
    li.appendChild(link);
    return li;
}

function showPreview(item, label) {
    var preview = document.getElementById('preview');
    var html = '<h3>' + label;
    if (item.title) {
        html += ': ' + item.title;
    }
    html += '</h3>';
    
    if (item.description) {
        html += '<p><strong>Description:</strong> ' + item.description + '</p>';
    }
    
    if (item.material) {
        html += '<p><strong>Material:</strong> ' + item.material + '</p>';
    }
    
    preview.innerHTML = html;
}

function showContentError(message) {
    var list = document.getElementById('contentList');
    if (list) {
        list.innerHTML = '<li style="color: red;">' + message + '</li>';
    }
}

