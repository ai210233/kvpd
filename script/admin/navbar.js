function toggleMenu() {
    var sidebar = document.getElementById('sidebar');
    var closeBtn = document.getElementById('close-btn');

    sidebar.style.left = sidebar.style.left === '0px' ? '-250px' : '0';
    closeBtn.style.left = closeBtn.style.left === '250px' ? '0' : '250px';
}