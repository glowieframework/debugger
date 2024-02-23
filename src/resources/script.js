document.addEventListener('DOMContentLoaded', () => {

    const debugBar = document.querySelector('.gdbg .gdbg-container');
    if(!debugBar) return;

    // -- Display debugger toggler
    const toggler = document.querySelector('.gdbg-toggler');
    if(!window.localStorage.getItem('gdbg-hide')) {
        debugBar.classList.add('gdbg-container-show');
    } else {
        toggler.classList.add('gdbg-toggler-show');
    }

    toggler.addEventListener('click', e => {
        e.preventDefault();
        toggler.classList.remove('gdbg-toggler-show');
        debugBar.classList.add('gdbg-container-show');
        window.localStorage.removeItem('gdbg-hide');
    });

    const closeBtn = document.querySelector('.gdbg-close');
    closeBtn.addEventListener('click', e => {
        e.preventDefault();
        debugBar.classList.remove('gdbg-container-show');
        toggler.classList.add('gdbg-toggler-show');
        window.localStorage.setItem('gdbg-hide', true);
    });

    // -- Resizer
    const panelHeight = window.localStorage.getItem('gdbg-height') || 200;
    debugBar.style.height = panelHeight + 'px';

    const minHeight = 100;
    const handleHeight = 5;
    let mousePosition;

    function resize(e) {
        let dx = mousePosition - e.y;
        mousePosition = e.y;
        let size = (parseInt(getComputedStyle(debugBar, '').height) + dx);
        if(size >= minHeight && size <= (window.innerHeight - 20)) {
            debugBar.style.height = size + 'px';
            window.localStorage.setItem('gdbg-height', size);
        }
    }

    debugBar.addEventListener('mousedown', function(e) {
        if(e.offsetY < handleHeight) {
            mousePosition = e.y;
            document.addEventListener('mousemove', resize, false);
        }
    }, false);

    document.addEventListener('mouseup', function() {
        document.removeEventListener('mousemove', resize, false);
    }, false);

    // -- Tab Switcher
    const tabs = document.querySelectorAll('.gdbg .gdbg-tab');
    const menus = document.querySelectorAll('.gdbg .gdbg-menu a:not(.gdbg-close)');
    const currentTab = window.localStorage.getItem('gdbg-tab') || '#gdbg-tab-1';

    menus.forEach(a => {
        a.addEventListener('click', e => {
            let id = a.getAttribute('data-target');
            e.preventDefault();
            toggleTab(id);
            a.classList.add('gdbg-menu-active');
            window.localStorage.setItem('gdbg-tab', id);
        });
    });

    function toggleTab(id) {
        tabs.forEach(t => t.classList.remove('gdbg-tab-active'));
        menus.forEach(m => m.classList.remove('gdbg-menu-active'));
        document.querySelector(id).classList.add('gdbg-tab-active');
    }

    toggleTab(currentTab);
    document.querySelector('a[data-target="' + currentTab + '"]').classList.add('gdbg-menu-active');

    // -- Trace toggler
    const togglers = document.querySelectorAll('.gdbg .gdbg-trace-toggle');

    togglers.forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            a.nextElementSibling.classList.toggle('gdbg-exception-trace-show');
        });
    });

    // -- Expandable toggler
    const expandables = document.querySelectorAll('.gdbg-expandable-value');

    expandables.forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            a.classList.toggle('gdbg-expandable-value-show');
        });
    });
});