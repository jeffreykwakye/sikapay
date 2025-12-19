document.addEventListener('DOMContentLoaded', function () {
    const logoForm = document.getElementById('logo-form');
    const confirmRemoveBtn = document.getElementById('confirm-remove-btn'); // Button inside the modal

    if (confirmRemoveBtn && logoForm) {
        confirmRemoveBtn.addEventListener('click', function (e) {
            e.preventDefault();

            // Change the form's action to the remove-logo route
            logoForm.action = '/company-profile/remove-logo';
            
            // We can remove the 'required' attribute from the file input if it exists,
            // as we are not uploading a file. This is crucial as no file is being uploaded for removal.
            const fileInput = document.getElementById('logo_file');
            if (fileInput) {
                fileInput.removeAttribute('required');
                // Also clear its value to prevent any accidental upload
                fileInput.value = ''; 
            }

            // Submit the form
            logoForm.submit();
        });
    }
    
    // Optional: Re-add 'required' attribute if the modal is dismissed without submitting
    // and user intends to upload again. For now, rely on form validation on submission.
});