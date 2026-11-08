/**
 * KL Universal Anti-Spam - Admin Dashboard Scripts
 */
(function($) {
	'use strict';

	$(function() {
		// Tab Switching
		$('.kl-nav-tab').on('click', function(e) {
			var target = $(this).attr('href');
			if (target && target.indexOf('#') === 0) {
				e.preventDefault();
				$('.kl-nav-tab').removeClass('active');
				$(this).addClass('active');

				$('.kl-tab-pane').hide();
				$(target).fadeIn(150);

				// Update URL hash without scroll
				if (history.pushState) {
					history.pushState(null, null, target);
				}
			}
		});

		// Activate tab from URL hash if present
		if (window.location.hash && $(window.location.hash).length) {
			$('.kl-nav-tab[href="' + window.location.hash + '"]').trigger('click');
		}

		// Clear Logs Confirmation
		$('#kl-clear-logs-btn').on('click', function(e) {
			if (!confirm('Are you sure you want to permanently clear all anti-spam logs? This cannot be undone.')) {
				e.preventDefault();
			}
		});

		// Details Toggle in Logs Table
		$('.kl-toggle-details').on('click', function(e) {
			e.preventDefault();
			var $row = $(this).closest('tr').next('.kl-details-row');
			$row.toggle();
		});

		// Test Simulator
		$('#kl-run-test-simulator').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var scenario = $('#kl-sim-scenario').val();
			var $resultBox = $('#kl-sim-result');

			$btn.prop('disabled', true).text('Simulating...');
			$resultBox.hide();

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'kl_simulate_test',
					scenario: scenario,
					nonce: $('#kl_admin_nonce').val()
				},
				success: function(res) {
					$btn.prop('disabled', false).text('Run Test');
					if (res.success && res.data) {
						$resultBox.html(
							'<div class="notice notice-info inline" style="padding: 12px; margin-top: 15px;">' +
							'<h4>Simulation Result: Action <strong>' + res.data.action.toUpperCase() + '</strong> (Risk Score: ' + res.data.score + '/100)</h4>' +
							'<p><strong>Signals Triggered:</strong> ' + (res.data.signals.length ? '' : 'None (clean submission)') + '</p>' +
							'<ul>' +
							res.data.signals.map(function(s) {
								return '<li><strong>' + s.name + '</strong> (+' + s.points + ' pts): ' + s.details + '</li>';
							}).join('') +
							'</ul>' +
							'</div>'
						).fadeIn();
					} else {
						$resultBox.html('<div class="notice notice-error inline"><p>Simulation failed.</p></div>').fadeIn();
					}
				},
				error: function() {
					$btn.prop('disabled', false).text('Run Test');
					$resultBox.html('<div class="notice notice-error inline"><p>Request error.</p></div>').fadeIn();
				}
			});
		});
	});
})(jQuery);
