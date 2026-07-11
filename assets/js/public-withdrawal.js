/**
 * Public withdrawal flow – AJAX step handler.
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
		}).then(function (response) {
			return response.json();
		});
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
			.then(function (response) {
				return response.json();
			})
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

	if (trigger && flow) {
		trigger.addEventListener('click', function () {
			trigger.hidden = true;
			flow.hidden = false;
			showStep(1);
		});
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
	 * Bind confirm/back handlers for dynamically injected Step 2 markup.
	 */
	function bindStep2Events() {
		var confirmForm = step2Container.querySelector('.eu-withdrawal__form--confirm');
		var backButton = step2Container.querySelector('.eu-withdrawal__back');

		if (backButton) {
			backButton.addEventListener('click', function () {
				hideError();
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
