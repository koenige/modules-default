/*
 * default module
 * dbupdate helpers (run all pending via XHR)
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/default
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */

(function () {
	var btn = document.getElementById('dbupdate-run-all');
	if (!btn) return;
	var running = false;
	var buttonsP = btn.closest('p');

	btn.addEventListener('click', async function () {
		if (running) return;
		running = true;
		if (buttonsP) buttonsP.style.display = 'none';
		var executed = 0;
		var msgEl = document.getElementById('dbupdate-message');
		if (msgEl) { msgEl.style.display = 'none'; msgEl.textContent = ''; }
		try {
			var cur = document.getElementById('current');
			if (!cur) return;
			var indexInput = cur.querySelector('input[name="index"]');
			if (!indexInput) return;
			var indexToSend = indexInput.value;
			var data;

			while (true) {
				var row = document.querySelector('tr[data-index="' + indexToSend + '"]');
				if (row) {
					var prev = document.getElementById('current');
					if (prev && prev !== row) {
						prev.classList.remove('current_record');
						prev.removeAttribute('id');
					}
					row.classList.add('current_record');
					row.id = 'current';
					row.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}

				var res = await fetch(location.pathname + location.search, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
						'X-Requested-With': 'XMLHttpRequest'
					},
					body: 'update=1&index=' + encodeURIComponent(indexToSend) + '&count=' + (executed + 1)
				});
				data = await res.json();
				if (!data || !data.ok) {
					if (msgEl && data && data.error) { msgEl.textContent = data.error; msgEl.style.display = ''; }
					if (row) {
						var td = row.querySelector('td:last-child');
						if (td && !td.querySelector('input[name="update"]')) {
							var p = document.createElement('p');
							p.innerHTML = '<input type="hidden" name="index" value="' + indexToSend + '">'
								+ '<input type="submit" name="update" value="Update">'
								+ '<input type="submit" name="ignore" value="Ignore">';
							td.appendChild(p);
						}
					}
					break;
				}
				executed++;

				if (row) {
					row.classList.remove('current_record');
					row.removeAttribute('id');
					row.classList.add('exists');
				}

				if (data.done) break;
				indexToSend = data.next_index;
			}
			if (msgEl && data && data.message) {
				msgEl.textContent = data.message;
				msgEl.style.display = '';
			}
		} catch (e) {} finally {
			running = false;
		}
	});
})();
