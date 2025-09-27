(function($){
	function getPageUrl(){
		return window.location.href.split('#')[0];
	}

	function openPinterestDialog(mediaUrl, description){
		var shareUrl = 'https://www.pinterest.com/pin/create/button/?' +
			'url=' + encodeURIComponent(getPageUrl()) +
			'&media=' + encodeURIComponent(mediaUrl) +
			'&description=' + encodeURIComponent(description || document.title);
		var w = 750, h = 550;
		var left = (screen.width/2)-(w/2);
		var top = (screen.height/2)-(h/2);
		return window.open(shareUrl, 'pin_create', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,width=' + w + ',height=' + h + ',top=' + top + ',left=' + left);
	}

	function showFollowModal(){
		if(!WPSF.profileUrl){ return; }
		var $modal = $('#wpsf-follow-modal');
		if(!$modal.length){ return; }
		var title = WPSF.followPromptTitle || 'Follow us on Pinterest';
		var body = WPSF.followPromptBody || '';
		var label = WPSF.followButtonLabel || 'Follow on Pinterest';
		var html = '' +
			'<div class="wpsf-modal-backdrop" role="dialog" aria-modal="true">' +
			'\t<div class="wpsf-modal">' +
			'\t\t<button type="button" class="wpsf-modal-close" aria-label="Close">×</button>' +
			'\t\t<h3 class="wpsf-modal-title">' + title + '</h3>' +
			'\t\t<div class="wpsf-modal-body">' + body + '</div>' +
			'\t\t<div class="wpsf-modal-actions">' +
			'\t\t\t<a class="wpsf-follow-button" href="' + WPSF.profileUrl + '" target="_blank" rel="noopener nofollow">' + label + '</a>' +
			'\t\t</div>' +
			'\t</div>' +
			'</div>';
		$modal.html(html).removeClass('wpsf-hidden');

		function closeModal(){
			$modal.addClass('wpsf-hidden').empty();
			$modal.off('.wpsf');
			$(document).off('keydown.wpsf');
		}

		// Reset any previous handlers in our namespace
		$modal.off('.wpsf');
		// Prevent clicks inside the dialog from reaching the backdrop
		$modal.on('mousedown.wpsfInside', '.wpsf-modal', function(e){ e.stopPropagation(); });
		$modal.on('click.wpsfInside', '.wpsf-modal', function(e){ e.stopPropagation(); });
		// Close when clicking the dimmed area only
		$modal.on('mousedown.wpsfBackdrop', '.wpsf-modal-backdrop', function(e){ if(e.target === this){ closeModal(); } });
		$modal.on('click.wpsfBackdrop', '.wpsf-modal-backdrop', function(e){ if(e.target === this){ e.stopPropagation(); } });
		// Close on X button (first click)
		$modal.on('mousedown.wpsfClose', '.wpsf-modal-close', function(e){ e.preventDefault(); e.stopPropagation(); closeModal(); });
		$modal.on('click.wpsfClose', '.wpsf-modal-close', function(e){ e.preventDefault(); e.stopPropagation(); });
		// Optional: close on Escape key
		$(document).off('keydown.wpsf').on('keydown.wpsf', function(e){ if(e.key === 'Escape'){ closeModal(); } });
	}

	function ensureOverlays(){
		if(!WPSF.autoOverlay){ return; }
		$('.wpsf-image-wrap').each(function(){
			var $wrap = $(this);
			if($wrap.data('wpsf-init')){ return; }
			$wrap.data('wpsf-init', true);
			var $img = $wrap.find('img.wpsf-image').first();
			if(!$img.length){ return; }
			var $btn = $('<button type="button" class="wpsf-save-btn" aria-label="Save to Pinterest"></button>');
			$btn.text(WPSF.overlayLabel || 'Save to Pinterest');
			$wrap.append($btn);
			$btn.on('click', function(e){
				e.preventDefault();
				var media = $img.attr('data-src') || $img.attr('src');
				var desc = $img.attr('alt') || document.title;
				var popup = null;
				if(media){ popup = openPinterestDialog(media, desc); }

				// Show modal after popup closes (best-effort detection)
				var shown = false;
				function showOnce(){ if(!shown){ shown = true; showFollowModal(); } }
				var checkInterval = setInterval(function(){
					if(popup && popup.closed){
						clearInterval(checkInterval);
						showOnce();
					}
				}, 300);
				// Fallback: when window regains focus (e.g., popup blocked or closed)
				function onFocus(){
					window.removeEventListener('focus', onFocus);
					clearInterval(checkInterval);
					showOnce();
				}
				window.addEventListener('focus', onFocus, { once: true });
				// Final fallback timeout
				setTimeout(function(){
					window.removeEventListener('focus', onFocus);
					clearInterval(checkInterval);
					showOnce();
				}, 10000);
			});
		});
	}

	$(document).ready(function(){
		ensureOverlays();
		// For dynamically loaded content
		var observer = new MutationObserver(function(){ ensureOverlays(); });
		observer.observe(document.body, { childList: true, subtree: true });
	});
})(jQuery);


