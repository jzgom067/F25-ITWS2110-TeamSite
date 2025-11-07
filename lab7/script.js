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
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1500);
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
});

