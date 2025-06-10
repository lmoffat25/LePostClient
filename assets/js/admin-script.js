// Le Post Client Admin Scripts

jQuery(document).ready(function($) {
    // Example: Click handler for a button
    // $('.my-plugin-button').on('click', function() {
    //     alert('Button clicked!');
    //     // You can add AJAX calls here, for example
    // });

    // console.log('Le Post Client admin script loaded.');

    // Generate Modal elements
    var generateModal = $('#lepc-generate-confirm-modal');
    var modalText = $('#lepc-modal-text');
    var modalIdeaIdInput = $('#lepc-modal-idea-id');
    var modalIdeaSubjectInput = $('#lepc-modal-idea-subject');
    var modalIdeaDescriptionInput = $('#lepc-modal-idea-description');
    var cancelGenerateModalButton = $('#lepc-modal-cancel-button'); // Renamed for clarity
    var confirmGenerateButton = $('#lepc-modal-confirm-generate-button');
    var generateModalForm = $('#lepc-generate-modal-form');

    // Edit Modal elements
    var editModal = $('#lepc-edit-idea-modal');
    var editIdeaIdInput = $('#lepc-edit-idea-id');
    var editIdeaSubjectInput = $('#lepc-edit-idea-subject');
    var editIdeaDescriptionInput = $('#lepc-edit-idea-description');
    var cancelEditModalButton = $('#lepc-edit-modal-cancel-button');
    var saveEditModalButton = $('#lepc-edit-modal-save-button'); // For potential future use (e.g. disabling on submit)
    var editModalForm = $('#lepc-edit-idea-form');

    // Delete Modal elements
    var deleteModal = $('#lepc-delete-confirm-modal');
    var deleteModalText = $('#lepc-delete-modal-text');
    var deleteModalIdeaIdInput = $('#lepc-delete-modal-idea-id');
    var cancelDeleteModalButton = $('#lepc-delete-modal-cancel-button');
    var confirmDeleteButton = $('#lepc-delete-modal-confirm-button');
    var deleteModalForm = $('#lepc-delete-modal-form');

    // Open Generate modal
    $(document).on('click', '.lepc-open-generate-modal-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var ideaId = button.data('idea-id');
        var ideaSubject = button.data('subject');
        var ideaDescription = button.data('description');

        modalText.text("Are you sure you want to generate a post for the idea: \"" + ideaSubject + "\"?"); 
        modalIdeaIdInput.val(ideaId);
        modalIdeaSubjectInput.val(ideaSubject);
        modalIdeaDescriptionInput.val(ideaDescription);
        confirmGenerateButton.prop('disabled', false).text('Confirm & Generate');
        generateModal.show();
    });

    // Close Generate modal
    cancelGenerateModalButton.on('click', function() {
        generateModal.hide();
    });

    // Handle Confirm & Generate button click (form submission is type=submit now)
    generateModalForm.on('submit', function() {
        confirmGenerateButton.prop('disabled', true).text('Initiating...');
        // Allow default form submission to proceed
    });

    // Open Edit modal
    $(document).on('click', '.lepc-open-edit-modal-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var ideaId = button.data('idea-id');
        var ideaSubject = button.data('subject');
        var ideaDescription = button.data('description');

        editIdeaIdInput.val(ideaId);
        editIdeaSubjectInput.val(ideaSubject);
        editIdeaDescriptionInput.val(ideaDescription);
        saveEditModalButton.prop('disabled', false).text('Save Changes');
        editModal.show();
    });

    // Close Edit modal
    cancelEditModalButton.on('click', function() {
        editModal.hide();
    });
    
    // Handle Save Changes button click (form submission is type=submit)
    editModalForm.on('submit', function() {
        saveEditModalButton.prop('disabled', true).text('Saving...');
        // Allow default form submission to proceed
    });

    // Open Delete modal
    $(document).on('click', '.lepc-open-delete-modal-button', function(e) {
        e.preventDefault();
        var button = $(this);
        var ideaId = button.data('idea-id');
        var ideaSubject = button.data('subject');

        deleteModalText.text("Are you sure you want to delete the idea: \"" + ideaSubject + "\"? This action cannot be undone."); 
        deleteModalIdeaIdInput.val(ideaId);
        confirmDeleteButton.prop('disabled', false).text('Confirm & Delete');
        deleteModal.show();
    });

    // Close Delete modal
    cancelDeleteModalButton.on('click', function() {
        deleteModal.hide();
    });

    // Handle Confirm & Delete button click (form submission is type=submit)
    deleteModalForm.on('submit', function() {
        confirmDeleteButton.prop('disabled', true).text('Deleting...');
        // Allow default form submission to proceed
    });

    // Optional: Close modals if user clicks outside the modal content
    $(window).on('click', function(event) {
        if ($(event.target).is(generateModal)) {
            generateModal.hide();
        }
        if ($(event.target).is(editModal)) {
            editModal.hide();
        }
        if ($(event.target).is(deleteModal)) {
            deleteModal.hide();
        }
    });

    // Handle AI Idea Generation form submission button state
    var aiGenerateForm = $('#lepc-ai-generate-form');
    var aiGenerateButton = $('#lepc_generate_ideas_ai_submit');

    if (aiGenerateForm.length && aiGenerateButton.length) {
        aiGenerateForm.on('submit', function() {
            // Check if the button is an input type=submit or a button element
            if (aiGenerateButton.is('input')) {
                aiGenerateButton.prop('disabled', true).val('Generating...');
            } else { // Assumes <button>
                aiGenerateButton.prop('disabled', true).text('Generating...');
            }
            // Allow default form submission to proceed
        });
    }

    // Handle CSV Import form submission button state
    var csvImportForm = $('#lepc-csv-import-form');
    var csvImportButton = $('#lepc_import_csv_submit'); // This is the name, jQuery can select by name for submit inputs

    if (csvImportForm.length && csvImportButton.length) {
        csvImportForm.on('submit', function() {
            if (csvImportButton.is('input')) {
                csvImportButton.prop('disabled', true).val('Importing...');
            } else { // Assumes <button>
                csvImportButton.prop('disabled', true).text('Importing...');
            }
            // Allow default form submission to proceed
        });
    }

    // Handle Bulk Actions form submission
    var bulkActionsForm = $('#lepc-ideas-list-form');
    var topApplyButton = $('#doaction');
    var bottomApplyButton = $('#doaction2');

    // Function to handle bulk action submission logic
    function handleBulkActionSubmit(event, applyButton) {
        var actionSelector;
        if (applyButton.attr('id') === 'doaction') {
            actionSelector = $('#bulk-action-selector-top');
        } else {
            actionSelector = $('#bulk-action-selector-bottom');
        }

        var selectedAction = actionSelector.val();
        var selectedIdeas = $('input[name="post_idea_ids[]"]:checked').length;

        if (selectedAction === '-1') {
            alert('Please select a bulk action.');
            event.preventDefault();
        }

        if (!event.defaultPrevented && selectedIdeas === 0) { 
            alert('Please select at least one idea to apply the bulk action.');
            event.preventDefault();
        }

        if (!event.defaultPrevented && selectedAction === 'bulk_delete') { 
            if (!confirm('Are you sure you want to delete the selected ' + selectedIdeas + ' idea(s)? This action cannot be undone.')) {
                event.preventDefault();
            }
        }
        
        if (event.defaultPrevented) {
            applyButton.prop('disabled', false);
            if (applyButton.attr('id') === 'doaction') {
                $('#doaction2').prop('disabled', false);
            } else {
                $('#doaction').prop('disabled', false);
            }
            return; 
        }

        applyButton.prop('disabled', true);
        if (applyButton.attr('id') === 'doaction') {
            $('#doaction2').prop('disabled', true); 
        } else {
            $('#doaction').prop('disabled', true); 
        }
        
        bulkActionsForm.get(0).submit(); 
    }

    if (bulkActionsForm.length) {
        topApplyButton.on('click', function(e) {
            handleBulkActionSubmit(e, $(this));
        });
        bottomApplyButton.on('click', function(e) {
            handleBulkActionSubmit(e, $(this));
        });

        // Also, if the form is submitted directly (e.g. by pressing Enter in a field if that were possible)
        // it's harder to determine which button/selector was intended.
        // For now, relying on click handlers for the 'Apply' buttons is the most straightforward.
    }
}); 