/**
 * Vishal Web Studio - Tech Relevant Theme Interactive Scripts
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Off-Canvas Sidebar Info Drawer Controller
    const sidebarTogglers = document.querySelectorAll('.sidebar-toggler-btn, [data-sidebar-toggler]');
    const sideDrawer = document.querySelector('.xs-sidebar-group');
    const closeDrawerBtn = document.querySelector('.close-side-widget');
    const overlay = document.querySelector('.xs-overlay');

    function openSidebar() {
        if (sideDrawer) sideDrawer.classList.add('isActive');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (sideDrawer) sideDrawer.classList.remove('isActive');
        document.body.style.overflow = '';
    }

    sidebarTogglers.forEach(btn => btn.addEventListener('click', (e) => {
        e.preventDefault();
        openSidebar();
    }));

    if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', (e) => {
        e.preventDefault();
        closeSidebar();
    });

    if (overlay) overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSidebar();
    });

    // 2. Floating Quick Chat Popup Controller
    const chatToggler = document.querySelector('.chat-toggler');
    const chatPopup = document.getElementById('chat-popup');
    const closeChatBtn = document.querySelector('.close-chat');

    if (chatToggler && chatPopup) {
        chatToggler.addEventListener('click', () => {
            chatPopup.classList.toggle('active');
        });
    }

    if (closeChatBtn && chatPopup) {
        closeChatBtn.addEventListener('click', () => {
            chatPopup.classList.remove('active');
        });
    }

    // 3. Mobile Navigation Drawer Controller
    const mobileToggler = document.querySelector('.mobile-nav__toggler');
    const navLinks = document.querySelector('.main-nav-links');

    if (mobileToggler && navLinks) {
        mobileToggler.addEventListener('click', (e) => {
            e.preventDefault();
            navLinks.classList.toggle('mobile-active');
        });
    }

    // 4. Device Switcher in Template Detail
    const deviceButtons = document.querySelectorAll('.device-btn');
    const previewIframe = document.getElementById('previewIframe');
    const previewContainer = document.getElementById('previewContainer');

    if (deviceButtons.length && previewIframe && previewContainer) {
        deviceButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                deviceButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const mode = btn.getAttribute('data-device');
                previewContainer.classList.remove('desktop', 'tablet', 'mobile');
                previewContainer.classList.add(mode);
            });
        });
    }

    // 5. Template Filter Buttons
    const filterButtons = document.querySelectorAll('.filter-btn');
    const templateCards = document.querySelectorAll('.template-card');

    if (filterButtons.length && templateCards.length) {
        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                filterButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const filter = btn.getAttribute('data-filter');
                templateCards.forEach(card => {
                    const category = card.getAttribute('data-category');
                    if (filter === 'all' || category === filter) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    }
});
