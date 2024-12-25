document.addEventListener("DOMContentLoaded", function () {
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const closeBtn = document.getElementById('close-btn');

    // Open sidebar
    hamburger.addEventListener('click', () => {
        sidebar.classList.add('show');
    });

    // Close sidebar
    closeBtn.addEventListener('click', () => {
        sidebar.classList.remove('show');
    });
     // Close sidebar if clicking outside of it
     document.addEventListener('click', (event) => {
        // Check if the click is outside the sidebar and not on the hamburger
        if (!sidebar.contains(event.target) && !hamburger.contains(event.target)) {
            sidebar.classList.remove('show');
        }
    });

    // Prevent closing the sidebar when clicking inside the sidebar
    sidebar.addEventListener('click', (event) => {
        event.stopPropagation(); // Prevent the click from propagating to the document listener
    });

});
