/**
 * Public withdrawal / return flow – AJAX step handler.
 */
(function () {
	'use strict';

	if (typeof euWithdrawalPublic === 'undefined') {
		return;
	}

	var config = euWithdrawalPublic;
	var app = document.getElementById('eu-withdrawal-app');

	if (!app) {
		return;
	}

	var trigger = app.querySelector('.eu-withdrawal__trigger');
	var flow = app.querySelector('.eu-withdrawal__flow');
	var step1Container = app.querySelector('.eu-withdrawal__step--1');
	var step2Container = app.querySelector('.eu-withdrawal__step--2');
	var step3Container = app.querySelector('.eu-withdrawal__step--3');
	var messages = app.querySelector('.eu-withdrawal__messages');
	var nonce = config.nonce || '';
	var detailsHtmlCache = '';

	/**
	 * Parse a fetch response as JSON with a clear error when HTML is returned.
	 *
	 * @param {Response} response Fetch response.
	 * @return {Promise<Object>}
	 */
	function parseJsonResponse(response) {
		return response.text().then(function (text) {
			var trimmed = (text || '').trim();

			if (!trimmed) {
				throw new Error(config.i18n.genericError);
			}

			if (trimmed.charAt(0) === '<') {
				throw new Error(config.i18n.serverError || config.i18n.genericError);
			}

			try {
				return JSON.parse(trimmed);
			} catch (error) {
				throw new Error(config.i18n.serverError || config.i18n.genericError);
			}
		});
	}

	/**
	 * POST to admin-ajax.php and parse JSON.
	 *
	 * @param {string} action AJAX action name.
	 * @param {FormData|Object} payload Request body fields.
	 * @return {Promise<Object>}
	 */
	function request(action, payload) {
		var body = payload instanceof FormData ? payload : new FormData();

		if (!(payload instanceof FormData)) {
			Object.keys(payload).forEach(function (key) {
				body.append(key, payload[key]);
			});
		}

		body.append('action', action);

		if (nonce) {
			body.append('nonce', nonce);
		}

		return fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		}).then(parseJsonResponse);
	}

	/**
	 * Show an inline error message.
	 *
	 * @param {string} text Message to display.
	 */
	function showError(text) {
		if (!messages) {
			return;
		}

		messages.textContent = text || config.i18n.genericError;
		messages.hidden = false;
	}

	/**
	 * Hide the inline error message area.
	 */
	function hideError() {
		if (messages) {
			messages.hidden = true;
			messages.textContent = '';
		}
	}

	/**
	 * Toggle loading state on the root container.
	 *
	 * @param {boolean} isLoading Whether a request is in progress.
	 */
	function setLoading(isLoading) {
		app.classList.toggle('is-loading', isLoading);
	}

	/**
	 * Fetch a fresh nonce to work around full-page cache plugins.
	 */
	function refreshNonce() {
		var body = new FormData();
		body.append('action', 'eu_withdrawal_refresh_nonce');

		return fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		})
			.then(parseJsonResponse)
			.then(function (data) {
				if (data && data.success && data.data && data.data.nonce) {
					nonce = data.data.nonce;
				}
			})
			.catch(function () {
				// Keep the localized nonce as fallback.
			});
	}

	/**
	 * Show a specific step container.
	 *
	 * @param {number} step Step number (1–3).
	 */
	function showStep(step) {
		[step1Container, step2Container, step3Container].forEach(function (container, index) {
			if (!container) {
				return;
			}

			container.hidden = index + 1 !== step;
		});
	}

	/**
	 * Toggle IBAN / courier panels based on request type selection.
	 *
	 * @param {HTMLElement} root Details form root.
	 */
	function syncRequestTypePanels(root) {
		var selected = root.querySelector('input[name="request_type"]:checked');
		var type = selected ? selected.value : '';
		var ibanPanel = root.querySelector('.eu-withdrawal__iban-panel');
		var courierPanel = root.querySelector('.eu-withdrawal__courier-panel');

		if (ibanPanel) {
			ibanPanel.hidden = type !== 'refund';
		}

		if (courierPanel) {
			courierPanel.hidden = type !== 'return';
		}
	}

	/**
	 * Clamp a qty input between its min and max (order quantity).
	 *
	 * @param {HTMLInputElement} input Qty number input.
	 * @param {boolean} forceEmpty Whether empty values should be forced to min.
	 */
	function clampQtyInput(input, forceEmpty) {
		var min = parseInt(input.min, 10);
		var max = parseInt(input.max, 10);

		if (isNaN(min) || min < 1) {
			min = 1;
		}

		if (isNaN(max) || max < min) {
			max = min;
		}

		var raw = String(input.value || '').trim();

		if (raw === '') {
			if (forceEmpty) {
				input.value = String(min);
			}
			return;
		}

		var value = parseInt(raw, 10);

		if (isNaN(value) || value < min) {
			input.value = String(min);
			return;
		}

		if (value > max) {
			input.value = String(max);
		}
	}

	/**
	 * Bind qty clamp handlers so users cannot exceed ordered quantity.
	 *
	 * @param {HTMLElement} root Details form root.
	 */
	function bindQtyLimits(root) {
		root.querySelectorAll('.eu-withdrawal__product-qty input[type="number"]').forEach(function (input) {
			input.addEventListener('input', function () {
				clampQtyInput(input, false);
			});

			input.addEventListener('change', function () {
				clampQtyInput(input, true);
			});

			input.addEventListener('blur', function () {
				clampQtyInput(input, true);
			});
		});
	}

	if (trigger && flow) {
		trigger.addEventListener('click', function () {
			trigger.hidden = true;
			flow.hidden = false;
			showStep(1);
		});
	}

	// Auto-open flow when triggered from My Account (no button).
	if (!trigger && flow) {
		flow.hidden = false;
		showStep(1);
	}

	var step1Form = app.querySelector('.eu-withdrawal__form--step1');

	if (step1Form) {
		step1Form.addEventListener('submit', function (event) {
			event.preventDefault();
			hideError();
			setLoading(true);

			var formData = new FormData(step1Form);

			request('eu_withdrawal_step1', formData)
				.then(function (data) {
					if (!data || !data.success) {
						throw new Error(
							(data && data.data && data.data.message) || config.i18n.genericError
						);
					}

					detailsHtmlCache = data.data.html;
					step2Container.innerHTML = data.data.html;
					showStep(2);
					bindStep2Events();
				})
				.catch(function (error) {
					showError(error.message || config.i18n.networkError);
				})
				.finally(function () {
					setLoading(false);
				});
		});
	}

	/**
	 * Bind handlers for details + confirm markup injected into step 2.
	 */
	function bindStep2Events() {
		var detailsForm = step2Container.querySelector('.eu-withdrawal__form--details');
		var confirmForm = step2Container.querySelector('.eu-withdrawal__form--confirm');
		var backButton = step2Container.querySelector('.eu-withdrawal__back');

		if (detailsForm) {
			syncRequestTypePanels(detailsForm);
			bindQtyLimits(detailsForm);

			detailsForm.querySelectorAll('input[name="request_type"]').forEach(function (radio) {
				radio.addEventListener('change', function () {
					syncRequestTypePanels(detailsForm);
				});
			});

			detailsForm.addEventListener('submit', function (event) {
				event.preventDefault();
				hideError();

				var checkedProducts = detailsForm.querySelectorAll(
					'input[name="product_items[]"]:checked'
				);

				if (!checkedProducts.length) {
					showError(config.i18n.selectProduct || config.i18n.genericError);
					return;
				}

				detailsForm
					.querySelectorAll('.eu-withdrawal__product-qty input[type="number"]')
					.forEach(function (input) {
						clampQtyInput(input, true);
					});

				setLoading(true);

				var formData = new FormData(detailsForm);

				request('eu_withdrawal_details', formData)
					.then(function (data) {
						if (!data || !data.success) {
							throw new Error(
								(data && data.data && data.data.message) || config.i18n.genericError
							);
						}

						step2Container.innerHTML = data.data.html;
						bindStep2Events();
					})
					.catch(function (error) {
						showError(error.message || config.i18n.networkError);
					})
					.finally(function () {
						setLoading(false);
					});
			});
		}

		if (backButton) {
			backButton.addEventListener('click', function () {
				hideError();

				if (confirmForm && detailsHtmlCache) {
					step2Container.innerHTML = detailsHtmlCache;
					bindStep2Events();
					return;
				}

				showStep(1);
			});
		}

		if (!confirmForm) {
			return;
		}

		confirmForm.addEventListener('submit', function (event) {
			event.preventDefault();
			hideError();
			setLoading(true);

			var formData = new FormData(confirmForm);

			request('eu_withdrawal_confirm', formData)
				.then(function (data) {
					if (!data || !data.success) {
						throw new Error(
							(data && data.data && data.data.message) || config.i18n.genericError
						);
					}

					step3Container.innerHTML = data.data.html;
					showStep(3);
				})
				.catch(function (error) {
					showError(error.message || config.i18n.networkError);
				})
				.finally(function () {
					setLoading(false);
				});
		});
	}

	refreshNonce();
})();
