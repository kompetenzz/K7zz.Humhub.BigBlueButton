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

    }

    module.export({
        init: init
    });

});