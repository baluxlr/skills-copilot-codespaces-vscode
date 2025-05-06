// DealRoom Submission Form JavaScript

jQuery(document).ready(function($) {
    const form = $('#dealroom-submission-form');
    const nextButton = $('#next-step');
    const prevButton = $('#prev-step');
    const saveDraftButton = $('#save-draft');
    const submitButton = $('#submit-deal');
    const saveStatus = $('#save-status');
    
    // Initialize form
    initForm();
    
    function initForm() {
        // Set up event listeners
        nextButton.on('click', goToNextStep);
        prevButton.on('click', goToPrevStep);
        saveDraftButton.on('click', saveDraft);
        form.on('submit', submitForm);
        
        // Set up file upload handlers
        setupFileUploads();
        
        // Set up dynamic fields
        setupDynamicFields();
        
        // Initialize review section
        initReview();
    }
    
    function setupFileUploads() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                const fieldName = input.getAttribute('name');
                const preview = document.getElementById(`${fieldName}_preview`);
                
                if (!file) return;
                
                // Check file size
                if (file.size > dealroomSubmission.max_file_size) {
                    alert(dealroomSubmission.i18n.file_too_large);
                    input.value = '';
                    return;
                }
                
                // Upload the file
                uploadFile(file, fieldName);
            });
        });
    }
    
    function uploadFile(file, fieldName) {
        const formData = new FormData();
        formData.append('action', 'dealroom_upload_file');
        formData.append('nonce', dealroomSubmission.nonce);
        formData.append('file', file);
        formData.append('field', fieldName);
        
        const postId = document.getElementById('post_id').value;
        if (postId) {
            formData.append('post_id', postId);
        }
        
        // Show loading state
        const preview = document.getElementById(`${fieldName}_preview`);
        const originalContent = preview.innerHTML;
        preview.innerHTML = `
            <div class="upload-loading">
                <div class="spinner"></div>
                <span>${dealroomSubmission.i18n.uploading}</span>
            </div>
        `;
        
        fetch(dealroomSubmission.ajaxurl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update hidden field with file URL or attachment ID
                if (fieldName === 'featured_image' || fieldName === 'company_logo') {
                    document.getElementById(`${fieldName}_id`).value = data.data.attachment_id;
                    preview.innerHTML = `<img src="${data.data.attachment_url}" alt="Preview">`;
                } else {
                    document.getElementById(`${fieldName}_url`).value = data.data.file_url;
                    preview.innerHTML = `
                        <div class="file-info">
                            <span class="dashicons dashicons-media-document"></span>
                            <span class="filename">${file.name}</span>
                        </div>
                    `;
                }
            } else {
                alert(data.data.message);
                preview.innerHTML = originalContent;
            }
        })
        .catch(error => {
            console.error('Error uploading file:', error);
            alert(dealroomSubmission.i18n.error);
            preview.innerHTML = originalContent;
        });
    }
    
    function saveDraft() {
        saveStatus.textContent = dealroomSubmission.i18n.saving;
        saveStatus.classList.add('saving');
        
        const formData = new FormData(form[0]);
        formData.append('action', 'dealroom_save_draft');
        formData.append('nonce', dealroomSubmission.nonce);
        
        fetch(dealroomSubmission.ajaxurl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.post_id) {
                    document.getElementById('post_id').value = data.data.post_id;
                }
                
                saveStatus.textContent = dealroomSubmission.i18n.saved;
                saveStatus.classList.remove('saving');
                saveStatus.classList.add('saved');
                
                setTimeout(() => {
                    saveStatus.textContent = '';
                    saveStatus.classList.remove('saved');
                }, 3000);
            } else {
                saveStatus.textContent = `${dealroomSubmission.i18n.error}: ${data.data.message}`;
                saveStatus.classList.remove('saving');
                saveStatus.classList.add('error');
                
                setTimeout(() => {
                    saveStatus.textContent = '';
                    saveStatus.classList.remove('error');
                }, 5000);
            }
        })
        .catch(error => {
            console.error('Error saving draft:', error);
            saveStatus.textContent = dealroomSubmission.i18n.error;
            saveStatus.classList.remove('saving');
            saveStatus.classList.add('error');
            
            setTimeout(() => {
                saveStatus.textContent = '';
                saveStatus.classList.remove('error');
            }, 5000);
        });
    }
    
    function submitForm(e) {
        if (e) e.preventDefault();
        
        // Validate all fields
        if (!validateForm()) {
            return;
        }
        
        // Disable submit button
        submitButton.prop('disabled', true);
        submitButton.text(dealroomSubmission.i18n.submitting);
        
        const formData = new FormData(form[0]);
        formData.append('action', 'dealroom_submit_deal');
        formData.append('nonce', dealroomSubmission.nonce);
        
        fetch(dealroomSubmission.ajaxurl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const successMessage = $('<div class="dealroom-message success"></div>')
                    .text(data.data.message);
                
                form.replaceWith(successMessage);
                
                // Redirect after delay
                if (data.data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.data.redirect;
                    }, 3000);
                }
            } else {
                submitButton.prop('disabled', false);
                submitButton.text(dealroomSubmission.i18n.submit);
                alert(data.data.message);
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            submitButton.prop('disabled', false);
            submitButton.text(dealroomSubmission.i18n.submit);
            alert(dealroomSubmission.i18n.error);
        });
    }
    
    function validateForm() {
        let isValid = true;
        
        // Validate required fields
        form.find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('invalid');
                
                if (!$(this).next('.error-message').length) {
                    $('<div class="error-message">This field is required.</div>')
                        .insertAfter(this);
                }
            } else {
                $(this).removeClass('invalid');
                $(this).next('.error-message').remove();
            }
        });
        
        // Validate terms agreement
        const termsAgreement = $('#terms_agreement');
        if (!termsAgreement.is(':checked')) {
            isValid = false;
            termsAgreement.addClass('invalid');
            
            if (!termsAgreement.next('.error-message').length) {
                $('<div class="error-message">You must agree to the terms.</div>')
                    .insertAfter(termsAgreement);
            }
        } else {
            termsAgreement.removeClass('invalid');
            termsAgreement.next('.error-message').remove();
        }
        
        return isValid;
    }
});