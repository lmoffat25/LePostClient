/**
 * Main entry point for the LePostClient admin JavaScript functionality.
 * Orchestrates all modal interactions, form submissions, and progress tracking.
 */
const main = () => {
	const $ = jQuery;
	
	// Initialize all modal handlers and UI interactions
	initGenerateModal($);
	initEditModal($);
	initDeleteModal($);
	initModalCloseBehavior($);
	initFormSubmissionHandlers($);
	initBulkActionsHandling($);
	initProgressTracking($);
}

/**
 * Initialize the generate post modal functionality
 * @param {Object} $ jQuery object
 */
const initGenerateModal = ($) => {
	// Generate Modal elements
	const generateModal = $('#lepc-generate-confirm-modal');
	const modalText = $('#lepc-modal-text');
	const modalIdeaIdInput = $('#lepc-modal-idea-id');
	const modalIdeaSubjectInput = $('#lepc-modal-idea-subject');
	const modalIdeaDescriptionInput = $('#lepc-modal-idea-description');
	const cancelGenerateModalButton = $('#lepc-modal-cancel-button');
	const confirmGenerateButton = $('#lepc-modal-confirm-generate-button');
	const generateModalForm = $('#lepc-generate-modal-form');
	const modalProgressContainer = $('#lepc-modal-progress-container');
	const modalProgressBar = $('#lepc-modal-progress-bar');
	const modalProgressText = $('#lepc-modal-progress-text');
	
	// Open Generate modal
	$(document).on('click', '.lepc-open-generate-modal-button', (e) => {
		e.preventDefault();
		const button = $(e.currentTarget);
		const ideaId = button.data('idea-id');
		const ideaSubject = button.data('subject');
		const ideaDescription = button.data('description');

		modalText.text(`Are you sure you want to generate a post for the idea: "${ideaSubject}"?`);
		modalIdeaIdInput.val(ideaId);
		modalIdeaSubjectInput.val(ideaSubject);
		modalIdeaDescriptionInput.val(ideaDescription);
		confirmGenerateButton.prop('disabled', false).text('Confirm & Generate');
		modalProgressContainer.hide();
		modalProgressBar.css('width', '0%');
		modalProgressText.text('0%');
		generateModal.show();
	});

	// Close Generate modal
	cancelGenerateModalButton.on('click', () => {
		generateModal.hide();
	});

	// Handle Confirm & Generate button click
	generateModalForm.on('submit', (e) => {
		// Prevent double submission
		if (confirmGenerateButton.prop('disabled')) {
			e.preventDefault();
			return false;
		}
		
		confirmGenerateButton.prop('disabled', true).text('Generating...');
		cancelGenerateModalButton.prop('disabled', true);
		modalText.text('Generating post, please wait...');
		
		// Show progress bar
		modalProgressContainer.show();
		
		// Start progress tracking for this idea
		const ideaId = modalIdeaIdInput.val();
		startProgressTracking(ideaId);
		
		// Allow default form submission to proceed
	});
}

/**
 * Initialize the edit idea modal functionality
 * @param {Object} $ jQuery object
 */
const initEditModal = ($) => {
	// Edit Modal elements
	const editModal = $('#lepc-edit-idea-modal');
	const editIdeaIdInput = $('#lepc-edit-idea-id');
	const editIdeaSubjectInput = $('#lepc-edit-idea-subject');
	const editIdeaDescriptionInput = $('#lepc-edit-idea-description');
	const cancelEditModalButton = $('#lepc-edit-modal-cancel-button');
	const saveEditModalButton = $('#lepc-edit-modal-save-button');
	const editModalForm = $('#lepc-edit-idea-form');

	// Open Edit modal
	$(document).on('click', '.lepc-open-edit-modal-button', (e) => {
		e.preventDefault();
		const button = $(e.currentTarget);
		const ideaId = button.data('idea-id');
		const ideaSubject = button.data('subject');
		const ideaDescription = button.data('description');

		editIdeaIdInput.val(ideaId);
		editIdeaSubjectInput.val(ideaSubject);
		editIdeaDescriptionInput.val(ideaDescription);
		saveEditModalButton.prop('disabled', false).text('Save Changes');
		editModal.show();
	});

	// Close Edit modal
	cancelEditModalButton.on('click', () => {
		editModal.hide();
	});
	
	// Handle Save Changes button click
	editModalForm.on('submit', () => {
		saveEditModalButton.prop('disabled', true).text('Saving...');
		// Allow default form submission to proceed
	});
}

/**
 * Initialize the delete idea modal functionality
 * @param {Object} $ jQuery object
 */
const initDeleteModal = ($) => {
	// Delete Modal elements
	const deleteModal = $('#lepc-delete-confirm-modal');
	const deleteModalText = $('#lepc-delete-modal-text');
	const deleteModalIdeaIdInput = $('#lepc-delete-modal-idea-id');
	const cancelDeleteModalButton = $('#lepc-delete-modal-cancel-button');
	const confirmDeleteButton = $('#lepc-delete-modal-confirm-button');
	const deleteModalForm = $('#lepc-delete-modal-form');

	// Open Delete modal
	$(document).on('click', '.lepc-open-delete-modal-button', (e) => {
		e.preventDefault();
		const button = $(e.currentTarget);
		const ideaId = button.data('idea-id');
		const ideaSubject = button.data('subject');

		deleteModalText.text(`Are you sure you want to delete the idea: "${ideaSubject}"? This action cannot be undone.`);
		deleteModalIdeaIdInput.val(ideaId);
		confirmDeleteButton.prop('disabled', false).text('Confirm & Delete');
		deleteModal.show();
	});

	// Close Delete modal
	cancelDeleteModalButton.on('click', () => {
		deleteModal.hide();
	});

	// Handle Confirm & Delete button click
	deleteModalForm.on('submit', () => {
		confirmDeleteButton.prop('disabled', true).text('Deleting...');
		// Allow default form submission to proceed
	});
}

/**
 * Initialize modal close behavior when clicking outside the modal
 * @param {Object} $ jQuery object
 */
const initModalCloseBehavior = ($) => {
	// Close modals if user clicks outside the modal content
	$(window).on('click', (event) => {
		const target = $(event.target);
		const modals = [
			'#lepc-generate-confirm-modal',
			'#lepc-edit-idea-modal',
			'#lepc-delete-confirm-modal'
		];
		
		modals.forEach(modalSelector => {
			if (target.is(modalSelector)) {
				$(modalSelector).hide();
			}
		});
	});
}

/**
 * Initialize form submission handlers for AI generation and CSV import
 * @param {Object} $ jQuery object
 */
const initFormSubmissionHandlers = ($) => {
	// Handle AI Idea Generation form submission
	const aiGenerateForm = $('#lepc-ai-generate-form');
	const aiGenerateButton = $('#lepc_generate_ideas_ai_submit');

	if (aiGenerateForm.length && aiGenerateButton.length) {
		aiGenerateForm.on('submit', () => {
			if (aiGenerateButton.is('input')) {
				aiGenerateButton.prop('disabled', true).val('Generating...');
			} else {
				aiGenerateButton.prop('disabled', true).text('Generating...');
			}
		});
	}

	// Handle CSV Import form submission
	const csvImportForm = $('#lepc-csv-import-form');
	const csvImportButton = $('#lepc_import_csv_submit');

	if (csvImportForm.length && csvImportButton.length) {
		csvImportForm.on('submit', () => {
			if (csvImportButton.is('input')) {
				csvImportButton.prop('disabled', true).val('Importing...');
			} else {
				csvImportButton.prop('disabled', true).text('Importing...');
			}
		});
	}
}

/**
 * Initialize bulk actions handling for the ideas list
 * @param {Object} $ jQuery object
 */
const initBulkActionsHandling = ($) => {
	const bulkActionsForm = $('#lepc-ideas-list-form');
	const topApplyButton = $('#doaction');
	const bottomApplyButton = $('#doaction2');

	if (!bulkActionsForm.length) return;

	// Function to handle bulk action submission logic
	const handleBulkActionSubmit = (event, applyButton) => {
		const isTopButton = applyButton.attr('id') === 'doaction';
		const actionSelector = isTopButton ? 
			$('#bulk-action-selector-top') : 
			$('#bulk-action-selector-bottom');

		const selectedAction = actionSelector.val();
		const selectedIdeas = $('input[name="post_idea_ids[]"]:checked').length;

		if (selectedAction === '-1') {
			alert('Please select a bulk action.');
			event.preventDefault();
		}

		if (!event.defaultPrevented && selectedIdeas === 0) { 
			alert('Please select at least one idea to apply the bulk action.');
			event.preventDefault();
		}

		if (!event.defaultPrevented && selectedAction === 'bulk_delete') { 
			if (!confirm(`Are you sure you want to delete the selected ${selectedIdeas} idea(s)? This action cannot be undone.`)) {
				event.preventDefault();
			}
		}
		
		// If bulk generate action is selected, show progress bars for selected ideas
		if (!event.defaultPrevented && selectedAction === 'bulk_generate') {
			$('input[name="post_idea_ids[]"]:checked').each(function() {
				const ideaId = $(this).val();
				const statusCell = $(this).closest('tr').find('.status.column-status');
				
				// Add progress bar to status cell if not already present
				if (statusCell.find('.lepc-small-progress-container').length === 0) {
					statusCell.html(`Generating <div class="lepc-small-progress-container"><div class="lepc-small-progress-bar" data-idea-id="${ideaId}"></div></div>`);
					startProgressTracking(ideaId);
				}
			});
		}
		
		if (event.defaultPrevented) {
			applyButton.prop('disabled', false);
			isTopButton ? $('#doaction2').prop('disabled', false) : $('#doaction').prop('disabled', false);
			return; 
		}

		applyButton.prop('disabled', true);
		isTopButton ? $('#doaction2').prop('disabled', true) : $('#doaction').prop('disabled', true);
		
		bulkActionsForm.get(0).submit(); 
	};

	topApplyButton.on('click', function(e) {
		handleBulkActionSubmit(e, $(this));
	});
	
	bottomApplyButton.on('click', function(e) {
		handleBulkActionSubmit(e, $(this));
	});
}

/**
 * Initialize progress tracking functionality for generating ideas
 * @param {Object} $ jQuery object
 */
const initProgressTracking = ($) => {
	// Check for generating ideas on page load and start progress tracking
	$('.lepc-small-progress-bar').each(function() {
		const ideaId = $(this).data('idea-id');
		if (ideaId) {
			startProgressTracking(ideaId);
		}
	});
}

// Store active polling intervals by idea ID
const activePollingIntervals = {};

/**
 * Start tracking progress for an idea using the task status API
 * @param {string} ideaId The ID of the idea being generated
 */
const startProgressTracking = (ideaId) => {
	const $ = jQuery;
	
	// Clear any existing interval for this idea
	if (activePollingIntervals[ideaId]) {
		clearInterval(activePollingIntervals[ideaId]);
	}
	
	// Function to update UI based on task status
	const updateProgressUI = (status) => {
		const progressPercentage = status.progress || 0;
		const statusText = status.status || 'processing';
		const stageMessage = status.stage_message || 'Processing content';
		
		// Update modal progress bar if visible
		const modalProgressContainer = $('#lepc-modal-progress-container');
		if (modalProgressContainer.is(':visible')) {
			$('#lepc-modal-progress-bar').css('width', progressPercentage + '%');
			$('#lepc-modal-progress-text').text(progressPercentage + '% - ' + stageMessage);
		}
		
		// Update table row progress bar and status
		const progressBar = $(`.lepc-small-progress-bar[data-idea-id="${ideaId}"]`);
		const statusCell = progressBar.closest('.status');
		
		progressBar.css('width', progressPercentage + '%');
		
		// If completed, update UI accordingly
		if (statusText === 'completed') {
			clearInterval(activePollingIntervals[ideaId]);
			
			// If we have a post ID, update the UI to show the view post link
			if (status.generated_post_id) {
				const row = progressBar.closest('tr');
				const actionsCell = row.find('.actions');
				
				// Update status cell
				statusCell.html('Completed');
				
				// Update actions cell with view post button
				if (status.post_edit_url) {
					actionsCell.html(`
						<a href="${status.post_edit_url}" class="button button-secondary" target="_blank">View Gen. Post</a>
						<button type="button" class="button button-secondary lepc-open-edit-modal-button" 
							data-idea-id="${ideaId}" 
							data-subject="${row.find('.subject').data('subject')}" 
							data-description="${row.find('.subject').data('description')}" 
							style="margin-left:5px;">Edit</button>
						<button type="button" class="button button-link-delete lepc-open-delete-modal-button" 
							data-idea-id="${ideaId}" 
							data-subject="${row.find('.subject').data('subject')}" 
							style="margin-left:5px; color: #d63638;">Delete</button>
					`);
				}
				
				// If modal is still open, close it and redirect to post edit page
				const generateModal = $('#lepc-generate-confirm-modal');
				if (generateModal.is(':visible')) {
					generateModal.hide();
					if (status.post_edit_url) {
						window.location.href = status.post_edit_url;
					} else {
						// Reload the page to refresh the list
						window.location.reload();
					}
				}
			}
		} else if (statusText === 'failed') {
			// Handle failed status
			clearInterval(activePollingIntervals[ideaId]);
			statusCell.html('Failed');
			
			// Show error message if available
			if (status.error_message) {
				alert('Post generation failed: ' + status.error_message);
			}
		}
	};
	
	// Start polling for status updates
	activePollingIntervals[ideaId] = setInterval(() => {
		// Make AJAX request to check status
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'lepostclient_check_generation_status',
				idea_id: ideaId,
				nonce: lepostclient_data.status_nonce
			},
			success: function(response) {
				if (response.success && response.data) {
					updateProgressUI(response.data);
				} else {
					console.error('Error checking generation status:', response);
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX error:', error);
			}
		});
	}, 3000); // Poll every 3 seconds as recommended
};

jQuery(document).ready(function($) {
	main();
});