document.addEventListener('DOMContentLoaded', () => {
    const debugBar = document.querySelector('.gdbg .gdbg-container');
    if(!debugBar) return;

    // -- Hide/show debug bar
    const toggler = document.querySelector('.gdbg-toggler');
    if(!window.localStorage.getItem('gdbg-hide')) {
        debugBar.classList.add('gdbg-container-show');
    } else {
        toggler.classList.add('gdbg-toggler-show');
    };

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

    // -- Resize debug bar
    const resizeHandle = document.querySelector('.gdbg .gdbg-resize-handle');
    const panelHeight = window.localStorage.getItem('gdbg-height') || 200;
    debugBar.style.height = panelHeight + 'px';

    const minHeight = 100;
    const maxHeight = window.innerHeight - 20;

    let mousePosition;

    function resize(e) {
        let dx = mousePosition - e.y;
        mousePosition = e.y;
        let size = (parseInt(getComputedStyle(debugBar, '').height) + dx);
        if(size >= minHeight && size <= maxHeight) {
            debugBar.style.height = size + 'px';
            window.localStorage.setItem('gdbg-height', size);
        };
    };

    resizeHandle.addEventListener('mousedown', function(e) {
        mousePosition = e.y;
        document.addEventListener('mousemove', resize, false);
    }, false);

    document.addEventListener('mouseup', function() {
        document.removeEventListener('mousemove', resize, false);
    }, false);

    // -- Tab Switcher
    const tabs = document.querySelectorAll('.gdbg .gdbg-tab');
    const menus = document.querySelectorAll('.gdbg .gdbg-menu button:not(.gdbg-close)');
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
    };

    toggleTab(currentTab);
    document.querySelector('.gdbg .gdbg-menu button[data-target="' + currentTab + '"]').classList.add('gdbg-menu-active');

    // -- Trace/params toggler
    const togglers = document.querySelectorAll('.gdbg .gdbg-trace-toggle');

    togglers.forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            a.nextElementSibling.classList.toggle('gdbg-exception-trace-show');
        });
    });

    // -- Expandable value toggler
    const expandables = document.querySelectorAll('.gdbg .gdbg-expandable-value');

    expandables.forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            a.classList.toggle('gdbg-expandable-value-show');
        });
    });

    // -- Console filtering
    const filters = document.querySelectorAll('.gdbg .gdbg-filter button');
    const messages = document.querySelectorAll('.gdbg .gdbg-row-message');
    const currentFilter = window.localStorage.getItem('gdbg-filter') || 'all';

    filters.forEach(a => {
        a.addEventListener('click', e => {
            let target = a.getAttribute('data-target');
            e.preventDefault();
            toggleFilter(target);
            a.classList.add('gdbg-filter-active');
            window.localStorage.setItem('gdbg-filter', target);
        });
    });

    function toggleFilter(target) {
        filters.forEach(f => f.classList.remove('gdbg-filter-active'));
        messages.forEach(message => {
            if(target == 'all' || message.classList.contains(target)) {
                message.classList.remove('gdbg-row-hidden');
            } else {
                message.classList.add('gdbg-row-hidden');
            };
        });
    };

    toggleFilter(currentFilter);
    document.querySelector('.gdbg .gdbg-filter button[data-target="' + currentFilter + '"]').classList.add('gdbg-filter-active');
});