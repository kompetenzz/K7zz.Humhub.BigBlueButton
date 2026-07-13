humhub.module('BBBHelpers', function (module, require, $) {

    const WINDOW_DIMS = [1280, 800];

    function LaunchBBBWindow(url) {
        // Get id from token query param or last path segment to use as window name for better session management
        // The url may be relative so we use the URL constructor to parse it, providing the current location as base
        const id = new URL(url, window.location.href).searchParams.get('token') || url.split('/').filter(Boolean).pop() || 'BBBWindow';
        const left = (screen.width - WINDOW_DIMS[0]) / 2;
        const top = (screen.height - WINDOW_DIMS[1]) / 2;
        window.open(url, id, `width=${WINDOW_DIMS[0]},height=${WINDOW_DIMS[1]},left=${left},top=${top},resizable=yes,scrollbars=yes,toolbar=yes,location=yes,status=yes,menubar=yes`);
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
                LaunchBBBWindow(this.dataset.url);
                document.dispatchEvent(new CustomEvent('bbb:launched'));
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
                LaunchBBBWindow(url);
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
        const waitingSelector = '#' + el.id + ' .bbb-waiting';
        const runningSelector = '#' + el.id + ' .bbb-running';

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

        function poll() {
            fetch(url)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.running) {
                        hasBeenRunning = true;
                    }
                    el.querySelectorAll('.bbb-hook-warning')
                        .forEach(w => w.style.display = data.hookFailed ? '' : 'none');
                    const currentState = el.dataset.bbbState || 'waiting';
                    const state = data.running ? 'running' : 'waiting';
                    // Let other widgets (e.g. the chat box) react to the meeting state
                    document.dispatchEvent(new CustomEvent('bbb:state', { detail: { running: !!data.running } }));
                    if (currentState && state !== currentState) {
                        if (redirectOnChange) {
                            console.log('Session state changed to', state, 'redirecting...');
                            window.location.reload();
                            return;
                        }
                        toggleSessionState(waitingSelector, runningSelector, data.running);
                        console.log('Session state changed to', state, 'updating display...');
                        el.dataset.bbbState = state;
                    }
                    if (launchBoost > 0) {
                        launchBoost--;
                        schedule(8000);
                    } else {
                        schedule(data.running ? RUNNING_IDLE_INTERVAL : interval);
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

    module.export({
        init: init,
        unload: unload,
        launchWindow: LaunchBBBWindow,
        toggleSessionState: toggleSessionState,
        reflectSessionState: reflectSessionState,
        setupDependent: setupDependent,
        setupDependentViceVersa: setupDependentViceVersa,
    });

});