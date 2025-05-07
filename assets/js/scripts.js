
// Order management functions
document.addEventListener('DOMContentLoaded', function() {
    // Image preview functionality
    initializeImagePreviews();
    // Delete confirmation handling
    initializeDeleteButtons();
    // Form validation
    initializeFormValidation();
});

function initializeImagePreviews() {
    const imagePreviewLinks = document.querySelectorAll('.image-preview-link');
    imagePreviewLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const imageUrl = this.getAttribute('data-image-url');
            document.getElementById('previewImage').src = imageUrl;
            document.getElementById('downloadImageLink').href = imageUrl;
        });
    });
}

function initializeDeleteButtons() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            document.querySelector('#deleteForm input[name="id"]').value = orderId;
        });
    });
}

function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}
