/**
 * BotFence - Frontend Anti-Spam & Automated Protection Engine
 *
 * Built by Amal S Kumar (https://amalskumar.dev)
 * Zero-dependency client script for behaviour telemetry, dynamic token refresh on cached pages,
 * and universal form builder protection.
 *
 * @version 1.0.0
 */
(function() {
	'use strict';

	// Prevent duplicate initialization.
	if (window.__botFenceInitialized) {
		return;
	}
	window.__botFenceInitialized = true;
	window.__klAntiSpamInitialized = true;

	var config = window.botfenceSettings || window.klAntiSpamSettings || {
		restUrl: '/wp-json/botfence/v1/',
		ajaxUrl: '/wp-admin/admin-ajax.php',
		nonce: '',
		tokenLifetime: 600,
		minTime: 2.5
	};

	var pageLoadTime = Date.now();
	var firstInteractionTime = null;
	var mouseMoves = 0;
	var keystrokes = 0;
	var touchEvents = 0;
	var focusEvents = 0;
	var pasteCount = 0;
	var autofillDetected = false;
	var lastMouseMove = 0;

	// Telemetry event listeners.
	function recordFirstInteraction() {
		if (firstInteractionTime === null) {
			firstInteractionTime = Date.now();
		}
	}

	window.addEventListener('mousemove', function() {
		recordFirstInteraction();
		var now = Date.now();
		// Throttle mouse moves to at most once every 50ms.
		if (now - lastMouseMove > 50) {
			mouseMoves++;
			lastMouseMove = now;
		}
	}, { passive: true });

	window.addEventListener('keydown', function() {
		recordFirstInteraction();
		keystrokes++;
	}, { passive: true });

	window.addEventListener('touchstart', function() {
		recordFirstInteraction();
		touchEvents++;
	}, { passive: true });

	window.addEventListener('focusin', function() {
		recordFirstInteraction();
		focusEvents++;
	}, { passive: true });

	window.addEventListener('paste', function() {
		recordFirstInteraction();
		pasteCount++;
	}, { passive: true });

	window.addEventListener('change', function(e) {
		recordFirstInteraction();
		if (e.target && (e.target.matches && e.target.matches(':-webkit-autofill') || e.target.getAttribute('autocomplete'))) {
			autofillDetected = true;
		}
	}, { passive: true });

	/**
	 * Encodes object to base64 string safely.
	 */
	function safeBtoa(str) {
		try {
			return window.btoa(unescape(encodeURIComponent(str)));
		} catch (e) {
			return '';
		}
	}

	/**
	 * Builds telemetry payload.
	 */
	function collectTelemetry(e) {
		var now = Date.now();
		var start = firstInteractionTime !== null ? firstInteractionTime : pageLoadTime;
		var elapsed = Math.max(0.01, (now - start) / 1000);

		var data = {
			elapsed_time: parseFloat(elapsed.toFixed(3)),
			mouse_moves: mouseMoves,
			keystrokes: keystrokes,
			touches: touchEvents,
			focus_count: focusEvents,
			paste_count: pasteCount,
			autofill: autofillDetected,
			is_trusted: e && typeof e.isTrusted !== 'undefined' ? !!e.isTrusted : true,
			webdriver: !!(navigator.webdriver || window._phantom || window.__nightmare),
			screen_dim: [window.screen ? window.screen.width : 0, window.screen ? window.screen.height : 0]
		};

		return safeBtoa(JSON.stringify(data));
	}

	/**
	 * Injects or updates anti-spam fields in a given form.
	 */
	function prepareForm(form) {
		if (!form || form.nodeName !== 'FORM') return;

		// Ensure primary BotFence token field exists.
		var tokenInput = form.querySelector('input.botfence-token, input[name="botfence_token"]');
		if (!tokenInput) {
			tokenInput = document.createElement('input');
			tokenInput.type = 'hidden';
			tokenInput.name = 'botfence_token';
			tokenInput.className = 'botfence-token kl-anti-spam-token';
			form.appendChild(tokenInput);
		}

		// Ensure legacy token input exists for maximum compatibility.
		var legacyToken = form.querySelector('input[name="kl_anti_spam_token"]');
		if (!legacyToken) {
			legacyToken = document.createElement('input');
			legacyToken.type = 'hidden';
			legacyToken.name = 'kl_anti_spam_token';
			form.appendChild(legacyToken);
		}

		// Ensure primary telemetry field exists.
		var telemetryInput = form.querySelector('input.botfence-telemetry, input[name="botfence_telemetry"]');
		if (!telemetryInput) {
			telemetryInput = document.createElement('input');
			telemetryInput.type = 'hidden';
			telemetryInput.name = 'botfence_telemetry';
			telemetryInput.className = 'botfence-telemetry kl-anti-spam-telemetry';
			form.appendChild(telemetryInput);
		}

		// Ensure legacy telemetry input exists.
		var legacyTelemetry = form.querySelector('input[name="kl_anti_spam_telemetry"]');
		if (!legacyTelemetry) {
			legacyTelemetry = document.createElement('input');
			legacyTelemetry.type = 'hidden';
			legacyTelemetry.name = 'kl_anti_spam_telemetry';
			form.appendChild(legacyTelemetry);
		}
	}

	/**
	 * Attach submit listener to capture telemetry immediately prior to submission.
	 */
	function attachFormSubmitHandler(form) {
		if (!form || form.__botFenceProtected) return;
		form.__botFenceProtected = true;
		form.__klProtected = true;
		prepareForm(form);

		form.addEventListener('submit', function(e) {
			var telemetryVal = collectTelemetry(e);
			var telemetryInputs = form.querySelectorAll('input.botfence-telemetry, input[name="botfence_telemetry"], input.kl-anti-spam-telemetry, input[name="kl_anti_spam_telemetry"]');
			for (var i = 0; i < telemetryInputs.length; i++) {
				telemetryInputs[i].value = telemetryVal;
			}
		}, true);
	}

	/**
	 * Scans the DOM for all forms and protects them.
	 */
	function scanAndProtectForms() {
		var forms = document.querySelectorAll('form');
		for (var i = 0; i < forms.length; i++) {
			var f = forms[i];
			var method = (f.getAttribute('method') || 'GET').toUpperCase();
			if (method === 'POST' || f.querySelector('.botfence-token') || f.querySelector('.kl-anti-spam-token') || f.classList.contains('wpcf7-form') || f.classList.contains('wpforms-form')) {
				attachFormSubmitHandler(f);
			}
		}
	}

	/**
	 * Refreshes tokens for cached pages.
	 */
	function refreshTokensForCachedPage() {
		var tokenInputs = document.querySelectorAll('input.botfence-token, input[name="botfence_token"], input.kl-anti-spam-token, input[name="kl_anti_spam_token"]');
		if (tokenInputs.length === 0) return;

		var requestUrl = config.restUrl + 'token';

		var xhr = new XMLHttpRequest();
		xhr.open('GET', requestUrl, true);
		xhr.setRequestHeader('X-WP-Nonce', config.nonce || '');

		xhr.onload = function() {
			if (xhr.status >= 200 && xhr.status < 300) {
				try {
					var response = JSON.parse(xhr.responseText);
					if (response && response.token) {
						for (var j = 0; j < tokenInputs.length; j++) {
							tokenInputs[j].value = response.token;
						}
					}
				} catch (err) {
					// Failover to existing token.
				}
			} else {
				fallbackAjaxRefresh(tokenInputs);
			}
		};

		xhr.onerror = function() {
			fallbackAjaxRefresh(tokenInputs);
		};

		xhr.send();
	}

	function fallbackAjaxRefresh(tokenInputs) {
		if (!config.ajaxUrl) return;
		var xhr = new XMLHttpRequest();
		xhr.open('POST', config.ajaxUrl, true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.onload = function() {
			if (xhr.status >= 200 && xhr.status < 300) {
				try {
					var response = JSON.parse(xhr.responseText);
					if (response && response.success && response.data && response.data.token) {
						for (var j = 0; j < tokenInputs.length; j++) {
							tokenInputs[j].value = response.data.token;
						}
					}
				} catch (e) {}
			}
		};
		xhr.send('action=botfence_refresh_token&nonce=' + encodeURIComponent(config.nonce || ''));
	}

	// Initialize on DOMContentLoaded.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function() {
			scanAndProtectForms();
			refreshTokensForCachedPage();
		});
	} else {
		scanAndProtectForms();
		refreshTokensForCachedPage();
	}

	// Also observe dynamically inserted forms.
	if (window.MutationObserver) {
		var observer = new MutationObserver(function(mutations) {
			for (var m = 0; m < mutations.length; m++) {
				var added = mutations[m].addedNodes;
				for (var n = 0; n < added.length; n++) {
					var node = added[n];
					if (node.nodeType === 1) {
						if (node.nodeName === 'FORM') {
							attachFormSubmitHandler(node);
						} else if (node.querySelectorAll) {
							var nested = node.querySelectorAll('form');
							for (var k = 0; k < nested.length; k++) {
								attachFormSubmitHandler(nested[k]);
							}
						}
					}
				}
			}
		});

		observer.observe(document.body || document.documentElement, {
			childList: true,
			subtree: true
		});
	}

	// Expose public API.
	window.BotFence = {
		refresh: refreshTokensForCachedPage,
		protectForm: attachFormSubmitHandler,
		getTelemetry: collectTelemetry
	};
	window.KLAntiSpam = window.BotFence;
})();
