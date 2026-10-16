/*
 * default module
 * block repeated submissions of POST forms (double or triple clicks)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


// release forms after this time, e. g. if the response was a download
var defSubmitOnceSeconds = %%% setting default_submit_once_seconds %%%;

document.addEventListener('submit', defSubmitOnce);
window.addEventListener('pageshow', defSubmitOnceReset);

/**
 * block a second submission of a POST form while the first one is sent
 *
 * forms that handle submission themselves (preventDefault, e. g. XHR),
 * forms opening in another window and forms with attribute
 * data-submit-repeat are ignored
 *
 * @param object event
 */
function defSubmitOnce(event) {
	var form = event.target;
	if ((form.getAttribute('method') || '').toLowerCase() !== 'post') return;
	if (form.hasAttribute('data-submit-repeat')) return;
	if (form.target && form.target !== '_self') return;
	if (form.classList.contains('submitting')) {
		event.preventDefault();
		return;
	}
	if (event.defaultPrevented) return;

	form.classList.add('submitting');
	// disable buttons only after the form data is collected,
	// otherwise the name of the clicked button would not be sent
	setTimeout(function() { defSubmitOnceButtons(form, true); }, 0);
	setTimeout(function() { defSubmitOnceRelease(form); }, defSubmitOnceSeconds * 1000);
}

/**
 * release forms if page is restored from back/forward cache
 *
 * @param object event
 */
function defSubmitOnceReset(event) {
	if (!event.persisted) return;
	var forms = document.querySelectorAll('form.submitting');
	for (var i = 0; i < forms.length; i++) {
		defSubmitOnceRelease(forms[i]);
	}
}

/**
 * allow form to be submitted again
 *
 * @param object form
 */
function defSubmitOnceRelease(form) {
	form.classList.remove('submitting');
	defSubmitOnceButtons(form, false);
}

/**
 * disable or enable submit buttons of a form; buttons that were disabled
 * before are left alone
 *
 * @param object form
 * @param bool disable
 */
function defSubmitOnceButtons(form, disable) {
	for (var i = 0; i < form.elements.length; i++) {
		var element = form.elements[i];
		if (element.type !== 'submit' && element.type !== 'image') continue;
		if (disable) {
			if (element.disabled) continue;
			element.disabled = true;
			element.setAttribute('data-submit-once', '');
		} else {
			if (!element.hasAttribute('data-submit-once')) continue;
			element.disabled = false;
			element.removeAttribute('data-submit-once');
		}
	}
}
