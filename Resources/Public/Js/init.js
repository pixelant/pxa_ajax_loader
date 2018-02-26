document.addEventListener("DOMContentLoaded", function () {
    if (typeof PxaAjaxLoader !== 'undefined') {
        PxaAjaxLoader.init({
            'querySelector': '[data-ajax-loader="1"]'
        });
    }
});