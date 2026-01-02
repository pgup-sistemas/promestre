// Custom Scripts
document.addEventListener('DOMContentLoaded', function() {
    // Enable tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Sidebar Toggle
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        // Uncomment below to persist sidebar toggle between refreshes
        // if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
        //     document.body.classList.toggle('sb-sidenav-toggled');
        // }
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.querySelector('#wrapper').classList.toggle('toggled');
            // localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }
});

function showToast(message, type, options) {
    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1100';
        document.body.appendChild(container);
    }
    var map = {
        success: 'text-bg-success',
        danger: 'text-bg-danger',
        warning: 'text-bg-warning',
        info: 'text-bg-info',
        primary: 'text-bg-primary',
        secondary: 'text-bg-secondary',
        light: 'text-bg-light',
        dark: 'text-bg-dark'
    };
    var iconMap = {
        success: 'fa-circle-check',
        danger: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info',
        primary: 'fa-bell',
        secondary: 'fa-bell',
        light: 'fa-bell',
        dark: 'fa-bell'
    };
    var cls = map[type] || 'text-bg-primary';
    var icon = iconMap[type] || iconMap['primary'];
    var closeClass = type === 'light' ? 'btn-close' : 'btn-close-white';
    var el = document.createElement('div');
    el.className = 'toast align-items-center ' + cls + ' border-0';
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.setAttribute('aria-atomic', 'true');
    el.innerHTML = '<div class="d-flex"><div class="toast-body"><i class="fa-solid ' + icon + ' me-2"></i>' + message + '</div><button type="button" class="' + closeClass + ' me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
    container.appendChild(el);
    var delay = options && options.delay ? options.delay : 4000;
    var autohide = options && options.autohide !== undefined ? options.autohide : true;
    var toast = new bootstrap.Toast(el, { delay: delay, autohide: autohide });
    toast.show();
}

// Copy Link Function
function copiarLink(id) {
    var copyText = document.getElementById(id);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    
    // Try using clipboard API first
    if (navigator.clipboard) {
        navigator.clipboard.writeText(copyText.value).then(function() {
            showToast("Link copiado para a área de transferência!", "success");
        }).catch(function(err) {
            console.error('Erro ao copiar: ', err);
            // Fallback
            document.execCommand("copy");
            showToast("Link copiado!", "success");
        });
    } else {
        // Fallback for older browsers
        document.execCommand("copy");
        showToast("Link copiado!", "success");
    }
}
