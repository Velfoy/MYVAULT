document.addEventListener("DOMContentLoaded", function () {
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const closeBtn = document.getElementById('close-btn');

    hamburger.addEventListener('click', () => {
        sidebar.classList.add('show');
    });

    closeBtn.addEventListener('click', () => {
        sidebar.classList.remove('show');
    });
     document.addEventListener('click', (event) => {
        if (!sidebar.contains(event.target) && !hamburger.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    });

    sidebar.addEventListener('click', (event) => {
        event.stopPropagation(); 
    });

});
