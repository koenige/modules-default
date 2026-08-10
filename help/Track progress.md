<!--
# default module
# Help: Track progress
#
# Part of »Zugzwang Project«
# https://www.zugzwang.org/modules/default
#
# @author Gustaf Mossakowski <gustaf@koenige.org>
# @copyright Copyright © 2026 Gustaf Mossakowski
# @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
#
# Variables
# audience = programmer
-->

# Track progress

`behaviour/track-progress.js` shows a progress bar, a log and a summary for
long-running operations. It supports two modes:

- **poll**: the operation runs on the server (e. g. as a background job), the
browser only polls a status URL and displays the progress.
- **drive**: the browser drives the operation itself, one POST request per
step. The server does nothing without the browser.

## Markup

Include a container with the class `track-progress` and add the script:

	<div class="track-progress" data-track-mode="poll" data-action="filecheck">
	…
	</div>
	%%% script default/track-progress.js %%%

Inside the container, elements are identified via `data-role`:

- `count`: text element, shows »done / total«
- `progress`: `<progress>` element
- `log`: `<ul>` element, log messages are appended as `<li>` (max. 200,
older entries are removed); error messages get `class="error"`
- `start`, `stop`: buttons (drive mode only; `stop` is optional)
- `status`: container that is unhidden when the operation starts (drive mode)
- `summary`: element for the final summary (drive mode)

Multiple containers per page are possible.

## Requests

All requests go as POST to the current URL
(`application/x-www-form-urlencoded`, `Accept: application/json`). The
parameter name is set via `data-action`, its value depends on mode and step.

The response must be JSON. If the payload is wrapped in a key (default:
`json`, changeable via `data-json-key`, disable with `data-json-key=""`),
it is unwrapped first; a string value is parsed as JSON again.

## Poll mode

	<div class="track-progress" data-track-mode="poll" data-interval="3000"
		data-action="filecheck" data-action-value="status"
		data-map-done="done" data-map-checked="checked" data-map-total="total">

Attributes:

- `data-interval`: poll interval in milliseconds (default 3000)
- `data-action-value`: value of the action parameter (default `status`)
- `data-map-done`, `data-map-checked`, `data-map-total`: JSON keys for the
status fields (defaults `done`, `checked`, `total`)
- `data-map-remaining`: alternative to `checked`/`total`: JSON key of a
shrinking »remaining« counter; the first response defines the total
- `data-reload-on-done`: `0` = do not reload the page when done (default:
reload)
- `data-max-errors`: stop polling after this many consecutive errors
(default 5)

Response fields:

- `done` (or mapped): truthy when the operation is finished
- `checked`, `total` (or mapped): progress numbers
- `messages`: optional array of log entries, either strings or objects
`{"text": "…", "is_error": true}`; rendered as text, never as HTML
- `ok`: optional; `false` together with `error` logs the error

Polling uses chained timeouts, so a slow server does not lead to overlapping
requests. Consecutive identical error messages are logged only once. The page
is only reloaded after a successful »done« response, never on errors.

## Drive mode

	<div class="track-progress" data-track-mode="drive" data-action="thumbnails">

Attributes:

- `data-init`: action value for the first request (default `init`)
- `data-finalize`: action value for the last request (default `finalize`);
set to an empty string to skip the finalize request, the summary of the
last step is shown instead
- `data-token-param`: parameter and JSON key for a session token
(default `token`)

Flow, after a click on the `start` button:

1. **Init request** (`action=init`). Response fields:
	- `ok`: `false` aborts, `error` is logged
	- `total`: number of steps; `0` ends the run, `message` is shown as
	summary
	- token (key as in `data-token-param`): optional; if set, it is sent
	with every following request
2. **Step requests** (`action=0`, `action=1`, …), one per step:
	- `ok` + `fatal`: a fatal error aborts the run, `error` is logged
	- `message`, `is_error`: optional log entry for this step
	- `summary`: optional, kept as summary fallback
	- `next`: optional object; its keys are sent as parameters with all
	following requests. Use this to pass state to the next step, e. g.
	a database cursor: `{"next": {"cursor": "12345"}}`
3. **Finalize request** (`action=finalize`, unless disabled) with the
parameters `processed` (number of processed steps) and `stopped`
(`0`/`1`); the token is included if there is one. The response field
`summary` is displayed.

The `stop` button stops the run after the current step; the finalize request
is still sent (with `stopped=1`). Network errors are logged and the buttons
are re-enabled, the run can be restarted.

Steps without a session are possible: if the init response contains no token
and each step response passes its state via `next`, the server needs no
session storage; aborted runs can simply be restarted.

## Examples

- Drive mode: `zzform` module, `zzbrick_make/thumbnails.inc.php` with template
`make-thumbnails.template.txt` (job list stored in the session, addressed via
token)
- Poll mode: `mediadb` module, import file check (progress from a shrinking
»remaining« counter via `data-map-remaining`)
