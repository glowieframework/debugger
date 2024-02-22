document.addEventListener('DOMContentLoaded', () => {

    const debugBar = document.querySelector('.gdbg .gdbg-container');
    if(!debugBar) return;

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
        if(size >= minHeight) {
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
    const menus = document.querySelectorAll('.gdbg .gdbg-menu a');

    menus.forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('gdbg-tab-active'));
            menus.forEach(m => m.classList.remove('gdbg-menu-active'));
            document.querySelector(a.getAttribute('data-target')).classList.add('gdbg-tab-active');
            a.classList.add('gdbg-menu-active');
        });
    });

    // -- Trace toggler
    const togglers = document.querySelectorAll('.gdbg .gdbg-trace-toggle');

    togglers.forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            a.nextElementSibling.classList.toggle('gdbg-exception-trace-show');
        });
    });
});