humhub.module('BBBHelpers', function (module, require, $) {

    const WINDOW_DIMS = [1280, 800];

    const status = require('ui.status');
    const client = require('client');

    function LaunchBBBWindow(url, target = url) {
        // Get id from token query param or last path segment to use as window name for better session management
        // The url may be relative so we use the URL constructor to parse it, providing the current location as base
        const id = new URL(target, window.location.href).searchParams.get('token') || target.split('/').filter(Boolean).pop() || 'BBBWindow';
        const left = (screen.width - WINDOW_DIMS[0]) / 2;
        const top = (screen.height - WINDOW_DIMS[1]) / 2;
        return window.open(url, id, `width=${WINDOW_DIMS[0]},height=${WINDOW_DIMS[1]},left=${left},top=${top},resizable=yes,scrollbars=yes,toolbar=yes,location=yes,status=yes,menubar=yes`);
    }

    // Max time to wait for the pre-join check before launching anyway
    const VERIFY_TIMEOUT = 8000;

    /*
     * Joins a running meeting, but asks BBB first (bypassing all caches) whether it
     * still exists - the page may show "running" long after the meeting has ended.
     * The window is opened blank right away, because browsers block popups opened
     * after an async request; it is closed again if the meeting is gone.
     */
    function verifyAndLaunch(trigger, url) {
        const stateEl = trigger.closest('[data-bbb-check-state]');
        if (!stateEl || !trigger.closest('.bbb-running')) {
            LaunchBBBWindow(url);
            return;
        }

        const win = LaunchBBBWindow('', url);
        // An already open BBB window (cross-origin) must not be closed or navigated blindly
        let isBlank = false;
        try { isBlank = !!win && win.location.href === 'about:blank'; } catch (e) { }

        const checkUrl = new URL(stateEl.dataset.bbbCheckState, window.location.href);
        checkUrl.searchParams.set('fresh', '1');
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), VERIFY_TIMEOUT);

        fetch(checkUrl, { signal: controller.signal })
            .then(r => r.json())
            .then(data => {
                if (data.running) {
                    launch();
                    return;
                }
                if (win && isBlank) {
                    win.close();
                }
                status.warn(data.message || '');
                stateEl.dispatchEvent(new CustomEvent('bbb:verified', { detail: data }));
            })
            .catch(e => {
                // Don't lock people out just because the check itself failed
                console.error('Pre-join check failed, launching anyway', e);
                launch();
            })
            .finally(() => clearTimeout(timeoutId));

        function launch() {
            if (win && isBlank) {
                win.location.href = url;
            } else {
                LaunchBBBWindow(url);
            }
            document.dispatchEvent(new CustomEvent('bbb:launched'));
        }
    }

    module.initOnPjaxLoad = true;

    var activeIntervals = [];
    var activeVisibilityListeners = [];
    var activeLaunchListeners = [];

    /*
     * Clears all active intervals to prevent memory leaks and unintended behavior after module unload
     * (Called by HumHub on module unload)
     */
    var unload = function () {

        // Clear all active intervals to prevent memory leaks and unintended behavior after module unload
        activeIntervals.forEach(id => clearInterval(id));
        activeIntervals = [];

        // Remove listeners tied to elements from the previous page, otherwise they pile up on every PJAX load
        activeVisibilityListeners.forEach(fn => document.removeEventListener('visibilitychange', fn));
        activeVisibilityListeners = [];

        activeLaunchListeners.forEach(fn => document.removeEventListener('bbb:launched', fn));
        activeLaunchListeners = [];
    };

    /*
     * Initializes event listeners for BBB session state reflection and window launching
     * (Autoloaded by HumHub on module load and after PJAX loads)
     */
    var init = function (isPjax) {

        unload();

        document.querySelectorAll('.bbb-launch-window').forEach(el => {
            el.addEventListener('click', function (e) {
                console.log('Launching BBB window for URL:', this.dataset.url);
                e.preventDefault();
                verifyAndLaunch(this, this.dataset.url);
            });
        });

        document.querySelectorAll('[data-bbb-check-state]').forEach(el => {
            reflectSessionState(el);
        });

        document.querySelectorAll('form[data-bbb-launch-window]').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const params = new URLSearchParams(new FormData(this));
                const url = this.action.split('?')[0] + '?' + params.toString();
                verifyAndLaunch(this, url);
            });
        });

    }

    function toggleSessionState(waitingSelector, runningSelector, running) {
        document.querySelectorAll(waitingSelector)
            .forEach(el => el.style.display = running ? 'none' : '');
        document.querySelectorAll(runningSelector)
            .forEach(el => el.style.display = running ? '' : 'none');
    }

    // Once a meeting is confirmed running, the only state change left to notice is
    // "it ended" - far less time-sensitive than "it just started", so back off hard.
    const RUNNING_IDLE_INTERVAL = 600000;
    // Elements showing live statistics need fresher data while running
    // (the server caches getMeetingInfo for 20s, so this stays cheap for BBB).
    const RUNNING_STATS_INTERVAL = 30000;

    function updateLiveStats(el, live) {
        el.querySelectorAll('[data-bbb-live-stats]').forEach(box => {
            if (!live) {
                box.style.display = 'none';
                return;
            }
            box.style.display = '';
            box.querySelectorAll('[data-bbb-stat]').forEach(item => {
                const key = item.dataset.bbbStat;
                const value = item.querySelector('.bbb-live-stat-value');
                if (key === 'recording') {
                    item.style.display = live.recording ? '' : 'none';
                } else if (key === 'startTime') {
                    value.textContent = live.startTime
                        ? new Date(live.startTime * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                        : '';
                } else if (value && key in live) {
                    value.textContent = live[key];
                }
            });
        });
    }

    function reflectSessionState(el, interval = 10000) {
        const id = el.id;
        if (!id) {
            console.error('Element with data-bbb-check-state is missing an id attribute:', el);
            return;
        }
        const url = el.dataset.bbbCheckState;
        if (!url) {
            console.error('Element with id', id, 'is missing url in data-bbb-check-state attribute');
            return;
        }

        const redirectOnChange = el.hasAttribute('data-bbb-redirect-on-change');
        const hasLiveStats = el.querySelector('[data-bbb-live-stats]') !== null;
        const waitingSelector = '#' + el.id + ' .bbb-waiting';
        const runningSelector = '#' + el.id + ' .bbb-running';
        // Take the initial state from the server-rendered markup unless it is given explicitly
        if (!el.dataset.bbbState) {
            const runningBlock = el.querySelector('.bbb-running');
            el.dataset.bbbState = runningBlock && runningBlock.style.display !== 'none' ? 'running' : 'waiting';
        }

        let timerId = null;
        // Until the meeting has actually started, a hidden tab must keep polling:
        // otherwise someone waiting to join in a background tab could miss the
        // entire meeting if it starts and ends while they're away. Once we've seen
        // it running at least once, "did it end" is low-stakes and can wait for
        // the tab to become visible again.
        let hasBeenRunning = false;
        // Number of fast follow-up polls still owed after a BBB window launch
        let launchBoost = 0;

        function clearTimer() {
            if (timerId !== null) {
                clearTimeout(timerId);
                const idx = activeIntervals.indexOf(timerId);
                if (idx !== -1) activeIntervals.splice(idx, 1);
                timerId = null;
            }
        }

        function schedule(delay) {
            clearTimer();
            if (hasBeenRunning && document.hidden) {
                return;
            }
            timerId = setTimeout(poll, delay);
            activeIntervals.push(timerId);
        }

        // Applies a state response; returns false if the page is being reloaded
        function applyState(data, allowReload = true) {
            if (data.running) {
                hasBeenRunning = true;
            }
            updateLiveStats(el, data.running ? data.live : null);
            el.querySelectorAll('.bbb-hook-warning')
                .forEach(w => w.style.display = data.hookFailed ? '' : 'none');
            const currentState = el.dataset.bbbState || 'waiting';
            const state = data.running ? 'running' : 'waiting';
            // Let other widgets (e.g. the chat box) react to the meeting state
            document.dispatchEvent(new CustomEvent('bbb:state', { detail: { running: !!data.running } }));
            if (currentState && state !== currentState) {
                if (redirectOnChange && allowReload) {
                    console.log('Session state changed to', state, 'redirecting...');
                    window.location.reload();
                    return false;
                }
                toggleSessionState(waitingSelector, runningSelector, data.running);
                console.log('Session state changed to', state, 'updating display...');
                el.dataset.bbbState = state;
            }
            return true;
        }

        function poll() {
            fetch(url)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!applyState(data)) {
                        return;
                    }
                    if (launchBoost > 0) {
                        launchBoost--;
                        schedule(8000);
                    } else {
                        schedule(data.running
                            ? (hasLiveStats ? RUNNING_STATS_INTERVAL : RUNNING_IDLE_INTERVAL)
                            : interval);
                    }
                })
                .catch((e) => {
                    console.error('Failed to fetch session state from', url, e);
                    schedule(interval);
                });
        }

        // Pause as soon as the tab is hidden (only once running has been seen -
        // see hasBeenRunning above), and resume immediately on return rather than
        // waiting for the next tick.
        function onVisibilityChange() {
            if (document.hidden) {
                if (hasBeenRunning) {
                    clearTimer();
                }
            } else if (timerId === null) {
                poll();
            }
        }
        document.addEventListener('visibilitychange', onVisibilityChange);
        activeVisibilityListeners.push(onVisibilityChange);

        // After the user launches the BBB window, poll quickly a few times: the
        // start request finishes within seconds and may carry the hook-failed flag.
        function onLaunched() {
            launchBoost = 2;
            schedule(8000);
        }
        document.addEventListener('bbb:launched', onLaunched);
        activeLaunchListeners.push(onLaunched);

        // A pre-join check found the meeting gone: fall back to the waiting state
        // right away and resume regular (waiting) polling.
        el.addEventListener('bbb:verified', function (e) {
            // No reload here, it would wipe the message shown to the user
            if (applyState(e.detail, false)) {
                schedule(interval);
            }
        });

        schedule(interval);
    }

    function setTooltip($anchor, text) {
        var el = $anchor[0];
        try { var t = bootstrap.Tooltip.getInstance(el); if (t) t.dispose(); } catch (e) { }
        $anchor.removeAttr('title').removeAttr('data-bs-original-title');
        if (text) {
            $anchor.attr('title', text);
            try { new bootstrap.Tooltip(el, { trigger: 'hover' }); } catch (e) { }
        }
    }

    function tooltipAnchor($el) {
        if ($el.is(':input')) {
            var $fc = $el.closest('.form-check');
            return $fc.length ? $fc : $el.closest('.form-group');
        }
        return $el;
    }

    // When any trigger checkbox is checked: all inputs within each target are disabled+tooltipped.
    // Checkboxes/radios are unchecked; other inputs are cleared via val('').
    function setupDependent(triggerSels, targetSels, msg, keepValues = false) {
        function update() {
            var active = triggerSels.some(function (s) { return $(s).is(':checked'); });
            targetSels.forEach(function (s) {
                var $el = $(s);
                var $inputs = $el.is(':input') ? $el : $el.find(':input');
                $el.css('opacity', active ? 0.75 : '');
                $inputs.prop('disabled', active);
                if (active && !keepValues) {
                    $inputs.filter(':checkbox, :radio').prop('checked', false);
                    $inputs.not(':checkbox, :radio').val('');
                }
                setTooltip(tooltipAnchor($el), active ? msg : null);
            });
        }
        $(triggerSels.join(', ')).on('change', update);
        update();
    }

    function setupDependentViceVersa(selsA, selsB, msgA, msgB) {
        setupDependent(selsA, selsB, msgA);
        setupDependent(selsB, selsA, msgB);
    }

    /*
     * Toggles a bookmark of the content-bookmarks module outside of a stream entry
     * (its own "bookmark" action only works within StreamEntry). The page is reloaded
     * afterwards, so the button state and the flash status message get rendered.
     */
    function toggleBookmark(evt) {
        evt.$trigger.addClass('disabled');
        client.post(evt).then(function () {
            client.reload();
        }).catch(function (err) {
            module.log.error(err, true);
            evt.$trigger.removeClass('disabled');
        });
    }

    module.export({
        init: init,
        unload: unload,
        launchWindow: LaunchBBBWindow,
        toggleSessionState: toggleSessionState,
        reflectSessionState: reflectSessionState,
        setupDependent: setupDependent,
        setupDependentViceVersa: setupDependentViceVersa,
        toggleBookmark: toggleBookmark,
    });

});