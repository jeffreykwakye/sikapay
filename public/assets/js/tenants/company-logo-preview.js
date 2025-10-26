/**
 * Global function to handle the client-side preview of the selected logo file.
 * This is called by the event listener setup below.
 * @param {string} currentLogoPath The path to the logo currently saved on the server.
 */
function previewFile(currentLogoPath) {
    const previewDiv = document.getElementById('logo-preview');
    const fileInput = document.getElementById('logo_file');
    const file = fileInput.files[0];
    
    // Clear previous content
    previewDiv.innerHTML = ''; 

    if (file) {
        // Validation Checks
        if (file.size > 1024 * 1024) { 
            previewDiv.innerHTML = '<p class="text-danger">File size exceeds the 1MB limit.</p>';
            fileInput.value = '';
            return;
        }
        if (!file.type.match('image/(png|jpeg)')) {
            previewDiv.innerHTML = '<p class="text-danger">Invalid file format. Please select PNG or JPG.</p>';
            fileInput.value = '';
            return;
        }

        const reader = new FileReader();

        reader.onloadend = function() {
            // Create and insert the new image tag
            const img = document.createElement('img');
            img.src = reader.result;
            img.alt = "New Logo Preview";
            img.style.cssText = 'height: 50px; border: 1px solid #eee; padding: 2px;';
            
            previewDiv.appendChild(img);
            
            // Add text indication
            const text = document.createElement('small');
            text.className = 'form-text text-success d-block';
            text.textContent = 'This is the new logo preview. Click "Upload/Update Logo" to save.';
            previewDiv.appendChild(text);
        }

        reader.readAsDataURL(file);
    } else {
        // Re-display the default/current logo if file is unselected
        if (currentLogoPath && currentLogoPath !== 'null' && currentLogoPath !== '') {
            previewDiv.innerHTML = `<p><img src="${currentLogoPath}" alt="Current Logo" style="height: 50px; border: 1px solid #eee; padding: 2px;"></p><small class="form-text text-muted">This is the logo currently saved in the system.</small>`;
        } else {
            previewDiv.innerHTML = '<p class="text-warning">No logo currently saved.</p>';
        }
    }
}

/**
 * Event Listener Setup
 * Binds the previewFile function to the change event of the file input once the DOM is ready.
 */
document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('logo_file');
    
    if (fileInput) {
        // Get the current logo path from the HTML data attribute
        const currentLogoPath = fileInput.getAttribute('data-current-logo-path');
        
        fileInput.addEventListener('change', () => {
            // When the file changes, call the core function
            previewFile(currentLogoPath);
        });
    }
});