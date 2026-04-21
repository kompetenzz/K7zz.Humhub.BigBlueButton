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

    /*
     * Clears all active intervals to prevent memory leaks and unintended behavior after module unload
     * (Called by HumHub on module unload)
     */
    var unload = function () {

        // Clear all active intervals to prevent memory leaks and unintended behavior after module unload
        activeIntervals.forEach(id => clearInterval(id));
        activeIntervals = [];
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

    function reflectSessionState(el, interval = 5000) {
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

        activeIntervals.push(setInterval(function () {
            fetch(url)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    const currentState = el.dataset.bbbState || 'waiting';
                    const state = data.running ? 'running' : 'waiting';
                    if (currentState && state !== currentState) {
                        if (redirectOnChange) {
                            console.log('Session state changed to', state, 'redirecting...');
                            window.location.reload();
                        } else {
                            toggleSessionState(waitingSelector, runningSelector, data.running);
                            console.log('Session state changed to', state, 'updating display...');
                            el.dataset.bbbState = state;
                        }
                    }
                })
                .catch((e) => {
                    console.error('Failed to fetch session state from', url, e);
                });
        }, interval));
    }

    module.export({
        init: init,
        unload: unload,
        launchWindow: LaunchBBBWindow,
        toggleSessionState: toggleSessionState,
        reflectSessionState: reflectSessionState,
    });

});