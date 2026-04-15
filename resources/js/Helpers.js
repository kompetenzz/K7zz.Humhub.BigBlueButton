humhub.module('BBBHelpers', function (module, require, $) {

    const WINDOW_DIMS = [1280, 800];

    function LaunchBBBWindow(url) {
        const left = (screen.width - WINDOW_DIMS[0]) / 2;
        const top = (screen.height - WINDOW_DIMS[1]) / 2;
        window.open(url, '_blank', `width=${WINDOW_DIMS[0]},height=${WINDOW_DIMS[1]},left=${left},top=${top},resizable=yes,scrollbars=yes,toolbar=no,location=no,status=no,menubar=no`);
    }

    module.initOnPjaxLoad = true;

    var init = function (isPjax) {

        document.querySelectorAll('.bbb-launch-window').forEach(el => {
            el.addEventListener('click', function (e) {
                console.log('Launching BBB window for URL:', this.dataset.url);
                e.preventDefault();
                LaunchBBBWindow(this.dataset.url);
            });
        });

        document.querySelectorAll('[data-bbb-check-running]').forEach(el => {
            const id = el.id;
            const url = el.dataset.bbbCheckRunning;
            if (!id) {
                console.error('Element with data-bbb-check-running is missing an id attribute:', el);
                return;
            }
            if (!url) {
                console.error('Element with id', id, 'is missing url in data-bbb-check-running attribute');
                return;
            }
            reflectSessionState(
                url,
                '#' + id + ' .bbb-waiting',
                '#' + id + ' .bbb-running'
            );
        });

    }

    function toggleSessionState(waitingSelector, runningSelector, running) {
        document.querySelectorAll(waitingSelector)
            .forEach(el => el.style.display = running ? 'none' : '');
        document.querySelectorAll(runningSelector)
            .forEach(el => el.style.display = running ? '' : 'none');
    }

    function reflectSessionState(url, waitingSelector, runningSelector, interval = 5000) {
        setInterval(function () {
            fetch(url)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    toggleSessionState(waitingSelector, runningSelector, data.running);
                })
                .catch(() => {
                    console.error('Failed to fetch session state from', url);
                });
        }, interval);
    }

    module.export({
        init: init,
        toggleSessionState: toggleSessionState,
        reflectSessionState: reflectSessionState,
    });

});