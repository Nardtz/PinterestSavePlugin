jQuery(document).ready(function($) {
	// Scan form submission
	$('#wppap-scan-form').on('submit', function(e) {
		e.preventDefault();
		
		var formData = {
			action: 'wppap_scan_posts',
			nonce: WPPAP.nonce,
			start_date: $('#scan_start_date').val(),
			end_date: $('#scan_end_date').val(),
			post_types: $('input[name="post_types[]"]:checked').map(function() {
				return this.value;
			}).get()
		};
		
		// Show progress
		$('#wppap-scan-progress').show();
		$('.progress-fill').css('width', '0%');
		$('.scan-status').text('Starting scan...');
		
		// Disable form
		$('#wppap-scan-form input, #wppap-scan-form button').prop('disabled', true);
		
		$.ajax({
			url: WPPAP.ajax_url,
			type: 'POST',
			data: formData,
			success: function(response) {
				if (response.success) {
					$('.progress-fill').css('width', '100%');
					$('.scan-status').text(response.data.message);
					
					// Show success message
					showNotice('success', response.data.message);
					
					// Refresh queue if on queue page
					if (window.location.href.indexOf('wppap-queue') !== -1) {
						location.reload();
					}
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
				// Re-enable form
				$('#wppap-scan-form input, #wppap-scan-form button').prop('disabled', false);
			}
		});
	});
	
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
	
	// Load Pinterest boards
	$('#wppap-load-boards').on('click', function(e) {
		e.preventDefault();
		
		var button = $(this);
		var originalText = button.text();
		
		button.prop('disabled', true).text('Loading...');
		
		$.ajax({
			url: WPPAP.ajax_url,
			type: 'POST',
			data: {
				action: 'wppap_get_boards',
				nonce: WPPAP.nonce
			},
			success: function(response) {
				if (response.success) {
					displayBoards(response.data.boards);
					showNotice('success', 'Boards loaded successfully!');
				} else {
					showNotice('error', response.data.message);
				}
			},
			error: function() {
				showNotice('error', 'Failed to load boards: Network error');
			},
			complete: function() {
				button.prop('disabled', false).text(originalText);
			}
		});
	});
	
	// Pin now action
	window.wppapPinNow = function(itemId) {
		if (!confirm('Are you sure you want to pin this image now?')) {
			return;
		}
		
		$.ajax({
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
	};
	
	// Remove from queue action
	window.wppapRemoveFromQueue = function(itemId) {
		if (!confirm('Are you sure you want to remove this item from the queue?')) {
			return;
		}
		
		$.ajax({
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
	};
	
	// Display Pinterest boards
	function displayBoards(boards) {
		var container = $('#wppap-board-list');
		container.empty();
		
		if (boards.length === 0) {
			container.html('<p>No boards found.</p>');
			return;
		}
		
		boards.forEach(function(board) {
			var boardItem = $('<div class="wppap-board-item" data-board-id="' + board.id + '">' +
				'<strong>' + board.name + '</strong><br>' +
				'<small>' + board.description + '</small>' +
				'</div>');
			
			boardItem.on('click', function() {
				$('.wppap-board-item').removeClass('selected');
				$(this).addClass('selected');
				$('#pinterest_board_id').val(board.id);
			});
			
			container.append(boardItem);
		});
	}
	
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
	
	// Auto-refresh queue every 30 seconds
	if (window.location.href.indexOf('wppap-queue') !== -1) {
		setInterval(function() {
			location.reload();
		}, 30000);
	}
});
