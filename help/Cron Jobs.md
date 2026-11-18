<!--
# default module
# Help: Cron Jobs
#
# Part of »Zugzwang Project«
# https://www.zugzwang.org/modules/default
#
# @author Gustaf Mossakowski <gustaf@koenige.org>
# @copyright Copyright © 2026 Gustaf Mossakowski
# @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
#
# Variables
# audience = admin
-->

# Cron Jobs

Scheduled jobs are rows in `_cronjobs`. A system cron job calls one page
on this website. That page runs `make cronjobs`, which requests every
active job whose schedule matches the current time.

The background job queue is a separate mechanism. It does not read
`_cronjobs`.

## Settings

- `cron_ips`: IP addresses (IPv4 or IPv6) of the server that runs the
system cron job. Requests from localhost (`127.0.0.1`, `::1`, or the
server’s own address) are allowed without an entry.
- `default_robot_username`: username of a local user with admin rights.
Each job URL is requested as this user.
- `login_key`: secret used to build the login token for that request.
`login_key_validity_in_minutes` limits how long a token stays valid
(default 15 minutes).
- `default_cronjobs_wait_seconds`: seconds to wait after one job request
finishes before the next job starts (default 5).
- `default_cronjobs_last_run`: time of the last run of `make cronjobs`.
The page writes this on every call, including when no job is due.

If jobs should run on another host, set `admin_hostname`. Job URLs that
start with `/` are then called on that host.

## Page called by the system cron

Create a webpage, for example `/jobs/cronjobs/`:

	%%% rights cron %%%
	%%% make cronjobs %%%
	%%% rights - %%%

The `cron` right allows the request when the client IP is in `cron_ips`,
when the request comes from localhost, or when an administrator is logged
in.

Call this page at least as often as the shortest interval you need. A
database job runs only when this page is requested and the current time
matches its schedule. Example, every minute, from the server:

	* * * * * curl -fsS https://example.org/jobs/cronjobs/

## Entering a job

Open the form on a page that includes:

	%%% forms cronjobs %%%

Fields:

- **Cron Job**: name, used for sorting.
- **URL**: path of the page to call, for example `/jobs/affiliate-prices/`.
- **Active**: `no` skips the job.
- **Minute**, **Hour**, **Day**, **Month**, **Weekday**: when the job may
run. Leave a field empty to allow every value. The list shows an empty
field as `*`.
- **Sequence**: optional. Jobs with a sequence run first, lowest number
first. Jobs without a sequence run afterwards, ordered by name and then
by URL.

For minute, hour, day and month you can enter one number or several
numbers separated by a comma, without spaces: `0,15,30,45`. Do not use
leading zeros. Ranges (`1-5`) and steps (`*/5`) are not supported.

Weekday takes a single number: `1` is Monday through `6` is Saturday,
`0` or `7` is Sunday. A comma-separated list is not supported for
weekday.

The page behind the URL should allow this call, for example:

	%%% rights cron %%%
	%%% make affiliate-prices %%%
	%%% rights - %%%

`make cronjobs` requests that URL with POST, authenticated as
`default_robot_username`. The user needs the rights that page requires.
A page limited to the `cron` right is reachable because the request
comes from this server.
