

document.addEventListener('DOMContentLoaded', function () {


    const sidebar =
        document.getElementById('sidebar');


    const toggle =
        document.getElementById('sidebarToggle');


    const close =
        document.getElementById('sidebarClose');


    const overlay =
        document.getElementById('sidebarOverlay');


    /* =====================================
       OPEN SIDEBAR
    ===================================== */

    if (!sidebar || !toggle || !close || !overlay) {
        return;
    }

    toggle.addEventListener('click', function () {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        toggle.setAttribute('aria-expanded', 'true');
    });


    /* =====================================
       CLOSE SIDEBAR
    ===================================== */

    function closeSidebar() {

        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        toggle.setAttribute('aria-expanded', 'false');
    }


    close.addEventListener(
        'click',
        closeSidebar
    );


    overlay.addEventListener(
        'click',
        closeSidebar
    );


    /* =====================================
       CLOSE AFTER CLICK MENU
       KHUSUS MOBILE
    ===================================== */

    sidebar
        .querySelectorAll('a')
        .forEach(function (link) {

            link.addEventListener(
                'click',
                function () {

                    if (
                        window.innerWidth <= 767
                    ) {

                        closeSidebar();

                    }

                }
            );

        });


});

