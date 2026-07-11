(function () {
	'use strict';

	var config = window.euWithdrawalAdmin || {};
	var i18n = config.i18n || {};

	function getConfirmMessage(status) {
		switch (status) {
			case 'rejected':
				return i18n.confirmReject || 'Are you sure you want to reject this withdrawal request?';
			case 'processed':
				return i18n.confirmProcessed || 'Are you sure you want to mark this request as processed?';
			case 'refunded':
				return i18n.confirmRefunded || 'Are you sure you want to mark this request as refunded?';
			case 'pending':
				return i18n.confirmPending || 'Are you sure you want to set this request back to pending?';
			default:
				return i18n.confirmGeneric || 'Are you sure you want to change the status of this request?';
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		var forms = document.querySelectorAll('.eu-wd-status-form');

		forms.forEach(function (form) {
			form.addEventListener('submit', function (event) {
				var select = form.querySelector('[name="new_status"]');
				var currentStatus = form.getAttribute('data-current-status') || '';
				var newStatus = select ? select.value : '';

				if (newStatus === currentStatus) {
					return;
				}

				if (!window.confirm(getConfirmMessage(newStatus))) {
					event.preventDefault();
				}
			});
		});
	});
})();
