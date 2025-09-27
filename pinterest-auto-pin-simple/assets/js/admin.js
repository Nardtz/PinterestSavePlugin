jQuery(document).ready(function($) {
	// Test Pinterest connection
	$('#wppap-test-connection').on('click', function(e) {
		e.preventDefault();
		
		var button = $(this);
		var originalText = button.text();
		
		button.prop('disabled', true).text('Testing...');
		
		$.ajax({
			url: WPPAP.ajax_url,
			type: 'POST',
			data: {
				action: 'wppap_test_connection',
				nonce: WPPAP.nonce
			},
			success: function(response) {
				if (response.success) {
					showNotice('success', response.data.message);
					$('#wppap-connection-status').removeClass('error').addClass('success').text('Connected successfully!');
				} else {
					showNotice('error', response.data.message);
					$('#wppap-connection-status').removeClass('success').addClass('error').text('Connection failed');
				}
			},
			error: function() {
				showNotice('error', 'Connection test failed: Network error');
				$('#wppap-connection-status').removeClass('success').addClass('error').text('Connection failed');
			},
			complete: function() {
				button.prop('disabled', false).text(originalText);
			}
		});
	});
	
	// Create table
	$('#wppap-create-table').on('click', function(e) {
		e.preventDefault();
		
		var button = $(this);
		var originalText = button.text();
		
		button.prop('disabled', true).text('Creating...');
		
		$.ajax({
			url: WPPAP.ajax_url,
			type: 'POST',
			data: {
				action: 'wppap_create_table',
				nonce: WPPAP.nonce
			},
			success: function(response) {
				if (response.success) {
					showNotice('success', response.data.message);
					$('#wppap-table-status').removeClass('error').addClass('success').text('Table created successfully!');
				} else {
					showNotice('error', response.data.message);
					$('#wppap-table-status').removeClass('success').addClass('error').text('Table creation failed');
				}
			},
			error: function() {
				showNotice('error', 'Table creation failed: Network error');
				$('#wppap-table-status').removeClass('success').addClass('error').text('Table creation failed');
			},
			complete: function() {
				button.prop('disabled', false).text(originalText);
			}
		});
	});
	
	// Process queue
	$('#wppap-process-queue').on('click', function(e) {
		e.preventDefault();
		
		var button = $(this);
		var originalText = button.text();
		
		button.prop('disabled', true).text('Processing...');
		
		$.ajax({
			url: WPPAP.ajax_url,
			type: 'POST',
			data: {
				action: 'wppap_process_queue',
				nonce: WPPAP.nonce
			},
			success: function(response) {
				if (response.success) {
					showNotice('success', response.data.message);
					$('#wppap-queue-status').removeClass('error').addClass('success').text('Queue processed successfully!');
				} else {
					showNotice('error', response.data.message);
					$('#wppap-queue-status').removeClass('success').addClass('error').text('Queue processing failed');
				}
			},
			error: function() {
				showNotice('error', 'Queue processing failed: Network error');
				$('#wppap-queue-status').removeClass('success').addClass('error').text('Queue processing failed');
			},
			complete: function() {
				button.prop('disabled', false).text(originalText);
			}
		});
	});
	
	// Start scan
	$('#wppap-start-scan').on('click', function(e) {
		e.preventDefault();
		
		var startDate = $('#scan_start_date').val();
		var endDate = $('#scan_end_date').val();
		
		if (!startDate || !endDate) {
			showNotice('error', 'Please select both start and end dates');
			return;
		}
		
		// Show progress
		$('#wppap-scan-progress').show();
		$('.progress-fill').css('width', '0%');
		$('.scan-status').text('Starting scan...');
		
		// Disable button
		$(this).prop('disabled', true).text('Scanning...');
		
		$.ajax({
			url: WPPAP.ajax_url,
			type: 'POST',
			data: {
				action: 'wppap_scan_posts',
				nonce: WPPAP.nonce,
				start_date: startDate,
				end_date: endDate
			},
			success: function(response) {
				if (response.success) {
					$('.progress-fill').css('width', '100%');
					$('.scan-status').text(response.data.message);
					showNotice('success', response.data.message);
				} else {
					$('.scan-status').text('Scan failed: ' + response.data.message);
					showNotice('error', 'Scan failed: ' + response.data.message);
				}
			},
			error: function() {
				$('.scan-status').text('Scan failed: Network error');
				showNotice('error', 'Scan failed: Network error');
			},
			complete: function() {
				$('#wppap-start-scan').prop('disabled', false).text('Start Scan');
			}
		});
	});
	
	// Show notice
	function showNotice(type, message) {
		var notice = $('<div class="wppap-notice ' + type + '">' + message + '</div>');
		$('.wrap h1').after(notice);
		
		setTimeout(function() {
			notice.fadeOut(function() {
				$(this).remove();
			});
		}, 5000);
	}
	
	// Set default dates
	var today = new Date();
	var lastWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
	
	$('#scan_end_date').val(today.toISOString().split('T')[0]);
	$('#scan_start_date').val(lastWeek.toISOString().split('T')[0]);
});

// Global functions for queue actions
function wppapPinNow(itemId) {
	if (!confirm('Are you sure you want to pin this image now?')) {
		return;
	}
	
	jQuery.ajax({
		url: WPPAP.ajax_url,
		type: 'POST',
		data: {
			action: 'wppap_pin_now',
			nonce: WPPAP.nonce,
			item_id: itemId
		},
		success: function(response) {
			if (response.success) {
				showNotice('success', response.data.message);
				location.reload();
			} else {
				showNotice('error', response.data.message);
			}
		},
		error: function() {
			showNotice('error', 'Failed to pin image: Network error');
		}
	});
}

function wppapRemoveFromQueue(itemId) {
	if (!confirm('Are you sure you want to remove this item from the queue?')) {
		return;
	}
	
	jQuery.ajax({
		url: WPPAP.ajax_url,
		type: 'POST',
		data: {
			action: 'wppap_remove_from_queue',
			nonce: WPPAP.nonce,
			item_id: itemId
		},
		success: function(response) {
			if (response.success) {
				showNotice('success', response.data.message);
				location.reload();
			} else {
				showNotice('error', response.data.message);
			}
		},
		error: function() {
			showNotice('error', 'Failed to remove item: Network error');
		}
	});
}
