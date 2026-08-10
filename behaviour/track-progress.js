/*
 * default module
 * track progress for background (poll) and client-driven (drive) operations
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2024-2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 *
 * Variables
 * translate_pot = admin
 */

(function () {
	function query(root, role) {
		return root.querySelector('[data-role="' + role + '"]');
	}

	function setProgress(root, done, total) {
		var countEl = query(root, 'count');
		var progressEl = query(root, 'progress');
		if (countEl) {
			countEl.textContent = done + ' / ' + total;
		}
		if (!progressEl) {
			return;
		}
		if (total > 0) {
			progressEl.max = total;
			progressEl.value = done;
			progressEl.textContent = Math.round((100 * done) / total) + '%';
		} else {
			progressEl.max = 1;
			progressEl.value = 0;
			progressEl.textContent = '0%';
		}
	}

	function appendLog(root, text, isError) {
		var logEl = query(root, 'log');
		if (!logEl) {
			return;
		}
		var li = document.createElement('li');
		if (isError) {
			li.className = 'error';
		}
		li.textContent = text;
		logEl.appendChild(li);
		while (logEl.children.length > 200) {
			logEl.removeChild(logEl.firstChild);
		}
	}

	function appendMessages(root, messages) {
		if (!Array.isArray(messages)) {
			return;
		}
		messages.forEach(function (message) {
			if (typeof message === 'string') {
				appendLog(root, message, false);
			} else if (message && typeof message.text === 'string') {
				appendLog(root, message.text, !!message.is_error);
			}
		});
	}

	function parsePayload(parsed, jsonKey) {
		var data = parsed;
		if (jsonKey) {
			if (!(jsonKey in parsed)) {
				return { ok: false, error: '%%% text Response has no `%s` key. %%%'.replace('%s', jsonKey) };
			}
			data = parsed[jsonKey];
		}
		if (typeof data === 'string') {
			try {
				data = JSON.parse(data);
			} catch (error) {
				return { ok: false, error: '%%% text `%s` value is not valid JSON. %%%'.replace('%s', jsonKey) };
			}
		}
		if (typeof data !== 'object' || data === null) {
			return { ok: false, error: '%%% text Progress response is not an object. %%%' };
		}
		return data;
	}

	async function fetchJson(root, actionValue, extra) {
		var action = root.dataset.action || 'check';
		var jsonKey = root.dataset.jsonKey;
		if (jsonKey === undefined) {
			jsonKey = 'json';
		}
		var params = new URLSearchParams();
		params.set(action, actionValue);
		if (extra) {
			Object.keys(extra).forEach(function (key) {
				params.set(key, extra[key]);
			});
		}
		var response = await fetch(window.location.href, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: params.toString()
		});
		var text = await response.text();
		try {
			return parsePayload(JSON.parse(text), jsonKey);
		} catch (error) {
			return { ok: false, error: text.slice(0, 200) || '%%% text Invalid JSON. %%%' };
		}
	}

	function readInitialProgress(root) {
		var progressEl = query(root, 'progress');
		if (!progressEl) {
			return;
		}
		var total = parseInt(progressEl.getAttribute('max'), 10);
		var done = parseInt(progressEl.getAttribute('value'), 10);
		if (!isNaN(total) && !isNaN(done) && total > 0) {
			setProgress(root, done, total);
		}
	}

	function initPoll(root) {
		var intervalTime = parseInt(root.dataset.interval, 10) || 3000;
		var mapDone = root.dataset.mapDone || 'done';
		var mapChecked = root.dataset.mapChecked || 'checked';
		var mapTotal = root.dataset.mapTotal || 'total';
		var mapRemaining = root.dataset.mapRemaining || '';
		var actionValue = root.dataset.actionValue || 'status';
		var reloadOnDone = root.dataset.reloadOnDone !== '0';
		var maxErrors = parseInt(root.dataset.maxErrors, 10) || 5;
		var initialRemaining = null;
		var consecutiveErrors = 0;
		var lastError = '';
		var finished = false;

		readInitialProgress(root);

		function schedule() {
			if (finished) {
				return;
			}
			setTimeout(poll, intervalTime);
		}

		function fail(message) {
			consecutiveErrors++;
			if (message && message !== lastError) {
				appendLog(root, message, true);
				lastError = message;
			}
			if (consecutiveErrors >= maxErrors) {
				finished = true;
				return;
			}
			schedule();
		}

		async function poll() {
			var data;
			try {
				data = await fetchJson(root, actionValue);
			} catch (error) {
				fail(String(error));
				return;
			}

			if (data.ok === false) {
				fail(data.error || '');
				return;
			}
			consecutiveErrors = 0;
			lastError = '';

			var checked = data[mapChecked];
			var total = data[mapTotal];
			if (mapRemaining && (checked === undefined || total === undefined)) {
				var remaining = data[mapRemaining];
				if (remaining !== undefined) {
					if (initialRemaining === null) {
						initialRemaining = remaining;
					}
					checked = initialRemaining - remaining;
					total = initialRemaining;
				}
			}
			if (checked !== undefined && total !== undefined) {
				setProgress(root, checked, total);
			}

			appendMessages(root, data.messages);

			if (data[mapDone]) {
				finished = true;
				if (reloadOnDone) {
					location.reload();
				}
				return;
			}
			schedule();
		}

		poll();
	}

	function initDrive(root) {
		var btnStart = query(root, 'start');
		var btnStop = query(root, 'stop');
		if (!btnStart) {
			return;
		}
		var statusEl = query(root, 'status');
		var summaryEl = query(root, 'summary');
		var tokenParam = root.dataset.tokenParam || 'token';
		var initStep = root.dataset.init || 'init';
		var finalizeStep = root.dataset.finalize !== undefined ? root.dataset.finalize : 'finalize';
		var stopFlag = false;

		function setButtons(running) {
			btnStart.disabled = running;
			if (btnStop) {
				btnStop.disabled = !running;
			}
		}

		async function drive() {
			var init = await fetchJson(root, initStep);
			if (!init.ok) {
				appendLog(root, init.error || '%%% text Init failed. %%%', true);
				return;
			}
			var token = init[tokenParam] || init.token || '';
			var total = init.total || 0;
			if (!total) {
				if (summaryEl) {
					summaryEl.textContent = init.message || '';
					summaryEl.hidden = false;
				}
				setProgress(root, 0, 0);
				return;
			}
			setProgress(root, 0, total);

			var index = 0;
			var fatalBreak = false;
			var lastSummary = '';
			var extra = {};
			if (token) {
				extra[tokenParam] = token;
			}

			while (index < total && !stopFlag) {
				var step = await fetchJson(root, String(index), extra);
				if (!step.ok && step.fatal) {
					appendLog(root, step.error || '%%% text Step failed. %%%', true);
					lastSummary = step.summary || step.error || '';
					fatalBreak = true;
					break;
				}
				if (step.message) {
					appendLog(root, step.message, !!step.is_error);
				}
				if (step.summary) {
					lastSummary = step.summary;
				}
				// server may pass state (e. g. a cursor) to subsequent steps
				if (step.next && typeof step.next === 'object') {
					Object.keys(step.next).forEach(function (key) {
						extra[key] = step.next[key];
					});
				}
				index++;
				setProgress(root, index, total);
			}

			if (!summaryEl) {
				return;
			}
			if (finalizeStep && !fatalBreak) {
				var finalizeParams = {
					processed: String(index),
					stopped: stopFlag ? '1' : '0'
				};
				if (token) {
					finalizeParams[tokenParam] = token;
				}
				var fin = await fetchJson(root, finalizeStep, finalizeParams);
				summaryEl.textContent = fin.summary || fin.error || '';
			} else {
				summaryEl.textContent = lastSummary;
			}
			summaryEl.hidden = false;
		}

		if (btnStop) {
			btnStop.addEventListener('click', function () {
				stopFlag = true;
				btnStop.disabled = true;
			});
		}

		btnStart.addEventListener('click', async function () {
			stopFlag = false;
			setButtons(true);
			var logEl = query(root, 'log');
			if (logEl) {
				logEl.innerHTML = '';
			}
			if (summaryEl) {
				summaryEl.hidden = true;
				summaryEl.textContent = '';
			}
			if (statusEl) {
				statusEl.hidden = false;
			}
			try {
				await drive();
			} catch (error) {
				appendLog(root, String(error), true);
			} finally {
				setButtons(false);
			}
		});
	}

	function init(root) {
		var mode = root.dataset.trackMode;
		if (mode === 'poll') {
			initPoll(root);
		} else if (mode === 'drive') {
			initDrive(root);
		}
	}

	document.querySelectorAll('.track-progress').forEach(init);
})();
