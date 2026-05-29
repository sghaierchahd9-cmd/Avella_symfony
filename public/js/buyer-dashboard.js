document.addEventListener('DOMContentLoaded', () => {

    /* =========================================================
       CATEGORY CAROUSEL
    ========================================================= */

    const wrapper = document.getElementById('categories-wrapper');

    let catOffset = 0;

    const CARD_WIDTH = 276;

    function getVisibleCards() {
        if (window.innerWidth < 768) return 1;
        if (window.innerWidth < 1024) return 2;
        return 4;
    }
    function getMaxOffset() {
        if (!wrapper) return 0;

        const totalCards = wrapper.children.length;
        const visible = getVisibleCards();

        if (totalCards <= visible) {
            return 0;
        }

        return -((totalCards - visible) * CARD_WIDTH);
    }

    function updateCategorySlider() {
        if (!wrapper) return;

        wrapper.style.transform = `translateX(${catOffset}px)`;
    }

    window.nextSlide = () => {
        if (!wrapper) return;

        catOffset -= CARD_WIDTH;

        if (catOffset < getMaxOffset()) {

            catOffset = 0;
        }

        updateCategorySlider();
    };

    window.prevSlide = () => {
        if (!wrapper) return;

        catOffset += CARD_WIDTH;

        if (catOffset > 0) {
            catOffset = getMaxOffset();
        }

        updateCategorySlider();
    };


    /* =========================================================
       HERO SLIDER
    ========================================================= */

    const slider = document.getElementById('slider');
    let currentSlide = 0;

    function getSlidesCount() {
        return slider ? slider.children.length : 0;
    }

    function updateHeroSlider() {
        if (!slider) return;

        slider.style.transform = `translateX(-${currentSlide * 100}%)`;
    }

    window.nextSlidepub = () => {
        currentSlide++;

        if (currentSlide >= getSlidesCount()) {
            currentSlide = 0;
        }

    window.nextSlidepub = function () {
        pubIndex++;
        if (pubIndex >= getSlideCount()) pubIndex = 0;
        updatePubSlider();
    };

    window.prevSlidepub = function () {
        pubIndex--;
        if (pubIndex < 0) pubIndex = getSlideCount() - 1;
        updatePubSlider();
    };

    /* Auto-advance hero slider every 4 s */
    if (slider) {
        setInterval(window.nextSlidepub, 4000);
    }


    /* ═══════════════════════════════════════════════════════════
       SIDEBAR TOGGLE
    ═══════════════════════════════════════════════════════════ */
    var sidebarWrapper = document.getElementById('sidebar-wrapper');
    var sidebar        = document.getElementById('sidebar');
    var openBtn        = document.getElementById('open-sidebar');
    var closeBtn       = document.getElementById('close-sidebar');
    var sidebarOverlay = document.getElementById('sidebar-overlay');

    function openSidebar() {
        if (!sidebarWrapper || !sidebar) return;
        sidebarWrapper.classList.remove('opacity-0', 'pointer-events-none');
        sidebarWrapper.classList.add('opacity-100');
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        sidebar.setAttribute('aria-hidden', 'false');
    }

    function closeSidebar() {
        if (!sidebarWrapper || !sidebar) return;
        sidebarWrapper.classList.add('opacity-0', 'pointer-events-none');
        sidebarWrapper.classList.remove('opacity-100');
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        sidebar.setAttribute('aria-hidden', 'true');
    }

    if (openBtn)        openBtn.addEventListener('click', openSidebar);
    if (closeBtn)       closeBtn.addEventListener('click', closeSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);}

});