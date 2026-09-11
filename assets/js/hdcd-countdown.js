/**
 * HDWebmobile Sales Countdown tick.
 *
 * For every element with a data-hdcd-deadline attribute (an ISO 8601 timestamp printed by
 * the server), update its text once a second to the remaining time. This script ONLY reads
 * that timestamp and ONLY writes textContent -- it never assigns innerHTML and never inserts
 * markup, so it cannot be a vector for injected content.
 */
(function () {
	'use strict';

	function pad(n) { return (n < 10 ? '0' : '') + n; }

	function format(msLeft) {
		var s = Math.max(0, Math.floor(msLeft / 1000));
		var d = Math.floor(s / 86400);
		var h = Math.floor((s % 86400) / 3600);
		var m = Math.floor((s % 3600) / 60);
		var sec = s % 60;
		if (d > 0) { return d + 'd ' + h + 'h ' + m + 'm'; }
		return pad(h) + ':' + pad(m) + ':' + pad(sec);
	}

	function tick() {
		var nodes = document.querySelectorAll('[data-hdcd-deadline]');
		var now = Date.now();
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			var deadline = Date.parse(el.getAttribute('data-hdcd-deadline'));
			if (isNaN(deadline)) { continue; }
			var left = deadline - now;
			if (left <= 0) {
				el.textContent = format(0);
				var wrap = el.closest('.hdcd-countdown, .hdcd-store-bar');
				if (wrap) { wrap.hidden = true; }
			} else {
				el.textContent = format(left);
			}
		}
	}

	tick();
	setInterval(tick, 1000);
})();
