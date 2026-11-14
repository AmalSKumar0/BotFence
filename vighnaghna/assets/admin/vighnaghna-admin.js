/**
 * Vighnaghna - WordPress Anti-Spam & Automated Form Protection
 * Admin Dashboard Scripts
 * Built by Amal S Kumar (https://amalskumar.dev)
 */
(function($) {
	'use strict';

	$(function() {
		// Tab Switching
		$(document).on('click', '.vighnaghna-nav-tab, .botfence-nav-tab, .kl-nav-tab', function(e) {
			var target = $(this).attr('href');
			if (target && target.indexOf('#') === 0) {
				e.preventDefault();
				$('.vighnaghna-nav-tab, .botfence-nav-tab, .kl-nav-tab').removeClass('active');
				$(this).addClass('active');

				$('.vighnaghna-tab-pane, .botfence-tab-pane, .kl-tab-pane').hide();
				$(target).show();

				// Update URL hash without jumping
				if (history.pushState) {
					history.pushState(null, null, target);
				}
			}
		});

		// Activate tab from URL hash or query param if present
		var activeHash = window.location.hash;
		if (!activeHash) {
			var urlParams = new URLSearchParams(window.location.search);
			var tabQuery = urlParams.get('tab');
			if (tabQuery) {
				activeHash = '#tab-' + tabQuery;
			}
		}
		if (activeHash && $(activeHash).length) {
			$('.vighnaghna-nav-tab[href="' + activeHash + '"], .botfence-nav-tab[href="' + activeHash + '"], .kl-nav-tab[href="' + activeHash + '"]').first().trigger('click');
		}

		// Switch tab button links (in-page buttons pointing to #tab-*)
		$(document).on('click', '.vighnaghna-switch-tab-btn, .botfence-switch-tab-btn', function(e) {
			var target = $(this).attr('href');
			if (target && target.indexOf('#tab-') === 0) {
				e.preventDefault();
				$('.vighnaghna-nav-tab[href="' + target + '"], .botfence-nav-tab[href="' + target + '"], .kl-nav-tab[href="' + target + '"]').first().trigger('click');
				var nav = $('.vighnaghna-nav-tabs, .botfence-nav-tabs');
				if (nav.length) {
					$('html, body').animate({ scrollTop: nav.offset().top - 40 }, 200);
				}
			}
		});

		// Refresh Live Stats Button
		$(document).on('click', '#vighnaghna-refresh-stats-btn, #botfence-refresh-stats-btn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var nonce = $('#vighnaghna_admin_nonce').val() || $('#botfence_admin_nonce').val() || $('#kl_admin_nonce').val();
			var $notice = $('#vighnaghna-stats-status-notice, #botfence-stats-status-notice');

			$btn.addClass('spinning').prop('disabled', true);

			$.ajax({
				url: typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php',
				type: 'POST',
				data: {
					action: 'vighnaghna_refresh_stats',
					nonce: nonce
				},
				success: function(res) {
					$btn.removeClass('spinning').prop('disabled', false);
					if (res.success && res.data) {
						var d = res.data;
						var formatNum = function(num) {
							return Number(num || 0).toLocaleString();
						};

						// Update Hero Metrics
						$('#vighnaghna-stat-hero-blocked, #botfence-stat-hero-blocked').text(formatNum(d.total_blocked)).addClass('vighnaghna-stat-pulse botfence-stat-pulse');
						$('#vighnaghna-stat-today-blocked, #botfence-stat-today-blocked').text(formatNum(d.blocked_today)).addClass('vighnaghna-stat-pulse botfence-stat-pulse');
						$('#vighnaghna-stat-7days-blocked, #botfence-stat-7days-blocked').text(formatNum(d.blocked_7_days)).addClass('vighnaghna-stat-pulse botfence-stat-pulse');
						$('#vighnaghna-stat-30days-blocked, #botfence-stat-30days-blocked').text(formatNum(d.blocked_30_days)).addClass('vighnaghna-stat-pulse botfence-stat-pulse');
						$('#vighnaghna-stat-rate, #botfence-stat-rate').text((d.block_rate || 0) + '%').addClass('vighnaghna-stat-pulse botfence-stat-pulse');

						// Update Grid Cards
						$('#vighnaghna-stat-total-checked, #botfence-stat-total-checked').text(formatNum(d.total_checked)).addClass('vighnaghna-stat-pulse botfence-stat-pulse');
						$('#vighnaghna-stat-total-allowed, #botfence-stat-total-allowed').text(formatNum(d.total_allowed)).addClass('vighnaghna-stat-pulse botfence-stat-pulse');
						$('#vighnaghna-stat-total-challenged, #botfence-stat-total-challenged').text(formatNum(d.total_challenged)).addClass('vighnaghna-stat-pulse botfence-stat-pulse');

						setTimeout(function() {
							$('.vighnaghna-stat-pulse, .botfence-stat-pulse').removeClass('vighnaghna-stat-pulse botfence-stat-pulse');
						}, 900);

						$notice.html('<span class="dashicons dashicons-yes-alt" style="font-size: 16px; vertical-align: middle;"></span> Live statistics updated successfully just now (' + new Date().toLocaleTimeString() + ')').slideDown(150);
						setTimeout(function() {
							$notice.slideUp(200);
						}, 3500);
					}
				},
				error: function() {
					$btn.removeClass('spinning').prop('disabled', false);
					$notice.html('<span class="dashicons dashicons-warning"></span> Could not refresh live stats.').slideDown(150);
					setTimeout(function() {
						$notice.slideUp(200);
					}, 3000);
				}
			});
		});

		// Clear Logs Confirmation
		$(document).on('click', '#vighnaghna-clear-logs-btn, #botfence-clear-logs-btn, #kl-clear-logs-btn', function(e) {
			if (!confirm('Are you sure you want to permanently clear all Vighnaghna anti-spam logs? This cannot be undone.')) {
				e.preventDefault();
			}
		});

		// Details Toggle in Logs Table
		$(document).on('click', '.vighnaghna-toggle-details, .botfence-toggle-details, .kl-toggle-details', function(e) {
			e.preventDefault();
			var $row = $(this).closest('tr').next('.vighnaghna-details-row, .botfence-details-row, .kl-details-row');
			$row.toggle();
		});

		// Test Simulator
		$(document).on('click', '#vighnaghna-run-test-simulator, #botfence-run-test-simulator, #kl-run-test-simulator', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var scenario = $('#vighnaghna-sim-scenario, #botfence-sim-scenario, #kl-sim-scenario').val();
			var $resultBox = $('#vighnaghna-sim-result, #botfence-sim-result, #kl-sim-result');
			var nonce = $('#vighnaghna_admin_nonce').val() || $('#botfence_admin_nonce').val() || $('#kl_admin_nonce').val();

			$btn.prop('disabled', true).text('Simulating Verification...');
			$resultBox.hide();

			$.ajax({
				url: typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php',
				type: 'POST',
				data: {
					action: 'vighnaghna_simulate_test',
					scenario: scenario,
					nonce: nonce
				},
				success: function(res) {
					$btn.prop('disabled', false).text('Run Test Simulation');
					if (res.success && res.data) {
						var actionColor = '#10b981';
						if (res.data.action === 'block') actionColor = '#ef4444';
						else if (res.data.action === 'challenge') actionColor = '#f59e0b';

						var html = '<div class="notice inline" style="padding: 16px; margin-top: 15px; border-left: 4px solid ' + actionColor + '; background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">' +
							'<h4 style="margin: 0 0 8px 0; font-size: 15px;">Vighnaghna Engine Decision: <span style="color:' + actionColor + '; text-transform: uppercase;">' + res.data.action + '</span> (Risk Score: ' + res.data.score + '/100)</h4>' +
							'<p style="margin: 0 0 8px 0; color: #475569;"><strong>Risk Evaluation:</strong> ' + (res.data.signals.length ? 'Suspicious signals detected below' : 'Clean submission — legitimate human interaction detected') + '</p>';

						if (res.data.signals && res.data.signals.length) {
							html += '<ul style="margin: 0; padding-left: 20px; color: #334155;">';
							res.data.signals.forEach(function(s) {
								html += '<li style="margin-bottom: 4px;"><strong>' + (s.name || s.code) + '</strong> (+' + s.points + ' pts): ' + s.details + '</li>';
							});
							html += '</ul>';
						}
						html += '</div>';

						$resultBox.html(html).fadeIn(200);
					} else {
						var errMsg = (res.data && res.data.message) ? res.data.message : 'Simulation failed.';
						$resultBox.html('<div class="notice notice-error inline" style="padding: 12px; margin-top: 15px;"><p>' + errMsg + '</p></div>').fadeIn(200);
					}
				},
				error: function() {
					$btn.prop('disabled', false).text('Run Test Simulation');
					$resultBox.html('<div class="notice notice-error inline" style="padding: 12px; margin-top: 15px;"><p>Request error while executing simulation.</p></div>').fadeIn(200);
				}
			});
		});
	});
})(jQuery);
