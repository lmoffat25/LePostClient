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
	generateModalForm.on('submit', () => {
		confirmGenerateButton.prop('disabled', true).text('Generating...');
		cancelGenerateModalButton.prop('disabled', true);
		modalText.text('Generating post, please wait...');
		
		// Show progress bar
		modalProgressContainer.show();
		
		// Start progress simulation
		const ideaId = modalIdeaIdInput.val();
		startProgressSimulation(ideaId);
		
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
					startProgressSimulation(ideaId);
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
	// Check for generating ideas on page load and start progress bars
	$('.lepc-small-progress-bar').each(function() {
		const ideaId = $(this).data('idea-id');
		if (ideaId) {
			startProgressSimulation(ideaId);
		}
	});
}

// Progress bar configuration
const progressConfig = {
	estimatedTime: 120000, // 120 seconds (adjust based on average generation time)
	updateInterval: 500, // Update every 500ms
	maxProgress: 95 // Maximum progress percentage before completion
};

// Store progress intervals by idea ID
const progressIntervals = {};

/**
 * Start simulating progress for an idea
 * @param {string} ideaId The ID of the idea being generated
 */
const startProgressSimulation = (ideaId) => {
	const $ = jQuery;
	
	// Clear any existing interval for this idea
	if (progressIntervals[ideaId]) {
		clearInterval(progressIntervals[ideaId]);
	}
	
	const startTime = Date.now();
	let currentProgress = 0;
	
	// Update both the modal progress bar and the table row progress bar
	progressIntervals[ideaId] = setInterval(() => {
		const elapsedTime = Date.now() - startTime;
		const progressPercentage = Math.min(
			Math.round((elapsedTime / progressConfig.estimatedTime) * 100),
			progressConfig.maxProgress
		);
		
		if (progressPercentage > currentProgress) {
			currentProgress = progressPercentage;
			
			// Update modal progress bar if visible
			const modalProgressContainer = $('#lepc-modal-progress-container');
			if (modalProgressContainer.is(':visible')) {
				$('#lepc-modal-progress-bar').css('width', currentProgress + '%');
				$('#lepc-modal-progress-text').text(currentProgress + '%');
			}
			
			// Update table row progress bar
			$(`.lepc-small-progress-bar[data-idea-id="${ideaId}"]`).css('width', currentProgress + '%');
			
			// If we've reached max progress, stop the interval
			if (currentProgress >= progressConfig.maxProgress) {
				clearInterval(progressIntervals[ideaId]);
			}
		}
	}, progressConfig.updateInterval);
};

jQuery(document).ready(function($) {
	main();
});