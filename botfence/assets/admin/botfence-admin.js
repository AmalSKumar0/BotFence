/**
 * BotFence - WordPress Anti-Spam & Automated CAPTCHA Protection
 * Admin Dashboard Scripts
 * Built by Amal S Kumar (https://amalskumar.dev)
 */
(function($) {
	'use strict';

	$(function() {
		// Tab Switching
		$(document).on('click', '.botfence-nav-tab, .kl-nav-tab', function(e) {
			var target = $(this).attr('href');
			if (target && target.indexOf('#') === 0) {
				e.preventDefault();
				$('.botfence-nav-tab, .kl-nav-tab').removeClass('active');
				$(this).addClass('active');

				$('.botfence-tab-pane, .kl-tab-pane').hide();
				$(target).fadeIn(150);

				// Update URL hash without jumping
				if (history.pushState) {
					history.pushState(null, null, target);
				}
			}
		});

		// Activate tab from URL hash if present
		if (window.location.hash && $(window.location.hash).length) {
			$('.botfence-nav-tab[href="' + window.location.hash + '"], .kl-nav-tab[href="' + window.location.hash + '"]').first().trigger('click');
		}

		// Switch tab button links (in-page buttons pointing to #tab-*)
		$(document).on('click', '.botfence-switch-tab-btn', function(e) {
			var target = $(this).attr('href');
			if (target && target.indexOf('#tab-') === 0) {
				e.preventDefault();
				$('.botfence-nav-tab[href="' + target + '"], .kl-nav-tab[href="' + target + '"]').first().trigger('click');
				$('html, body').animate({ scrollTop: $('.botfence-nav-tabs').offset().top - 40 }, 200);
			}
		});

		// Refresh Live Stats Button
		$(document).on('click', '#botfence-refresh-stats-btn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var nonce = $('#botfence_admin_nonce').val() || $('#kl_admin_nonce').val();
			var $notice = $('#botfence-stats-status-notice');

			$btn.addClass('spinning').prop('disabled', true);

			$.ajax({
				url: typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php',
				type: 'POST',
				data: {
					action: 'botfence_refresh_stats',
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
						$('#botfence-stat-hero-blocked').text(formatNum(d.total_blocked)).addClass('botfence-stat-pulse');
						$('#botfence-stat-today-blocked').text(formatNum(d.blocked_today)).addClass('botfence-stat-pulse');
						$('#botfence-stat-7days-blocked').text(formatNum(d.blocked_7_days)).addClass('botfence-stat-pulse');
						$('#botfence-stat-30days-blocked').text(formatNum(d.blocked_30_days)).addClass('botfence-stat-pulse');
						$('#botfence-stat-rate').text((d.block_rate || 0) + '%').addClass('botfence-stat-pulse');

						// Update Grid Cards
						$('#botfence-stat-total-checked').text(formatNum(d.total_checked)).addClass('botfence-stat-pulse');
						$('#botfence-stat-total-allowed').text(formatNum(d.total_allowed)).addClass('botfence-stat-pulse');
						$('#botfence-stat-total-challenged').text(formatNum(d.total_challenged)).addClass('botfence-stat-pulse');

						setTimeout(function() {
							$('.botfence-stat-pulse').removeClass('botfence-stat-pulse');
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
		$(document).on('click', '#botfence-clear-logs-btn, #kl-clear-logs-btn', function(e) {
			if (!confirm('Are you sure you want to permanently clear all BotFence anti-spam logs? This cannot be undone.')) {
				e.preventDefault();
			}
		});

		// Details Toggle in Logs Table
		$(document).on('click', '.botfence-toggle-details, .kl-toggle-details', function(e) {
			e.preventDefault();
			var $row = $(this).closest('tr').next('.botfence-details-row, .kl-details-row');
			$row.toggle();
		});

		// Test Simulator
		$(document).on('click', '#botfence-run-test-simulator, #kl-run-test-simulator', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var scenario = $('#botfence-sim-scenario, #kl-sim-scenario').val();
			var $resultBox = $('#botfence-sim-result, #kl-sim-result');
			var nonce = $('#botfence_admin_nonce').val() || $('#kl_admin_nonce').val();

			$btn.prop('disabled', true).text('Simulating Verification...');
			$resultBox.hide();

			$.ajax({
				url: typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php',
				type: 'POST',
				data: {
					action: 'botfence_simulate_test',
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
							'<h4 style="margin: 0 0 8px 0; font-size: 15px;">BotFence Engine Decision: <span style="color:' + actionColor + '; text-transform: uppercase;">' + res.data.action + '</span> (Risk Score: ' + res.data.score + '/100)</h4>' +
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
