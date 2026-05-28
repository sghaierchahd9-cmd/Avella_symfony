/**
 * js/buyer-dashboard.js
 *
 * Handles:
 *   - Category card carousel  (prevSlide / nextSlide)
 *   - Hero image slider       (prevSlidepub / nextSlidepub + auto-advance)
 *
 * FIX: All DOM access is deferred inside DOMContentLoaded so the script
 * can safely be loaded with or without `defer`. maxOffset is computed
 * lazily (inside the slide functions) so it always reflects the actual
 * number of category cards in the DOM, whether they come from PHP/DB
 * or are static.
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ═══════════════════════════════════════════════════════════
       CATEGORY CAROUSEL
       ─────────────────────────────────────────────────────────
       Cards are 260px wide + 2×8px horizontal margin = 276px total.
       We show 4 cards at a time on desktop.
       maxOffset is recalculated every slide so it always matches
       the actual number of cards (DB-driven count).
    ═══════════════════════════════════════════════════════════ */
    var wrapper   = document.getElementById('categories-wrapper');
    var catOffset = 0;
    var CARD_W    = 276; // 260px card + 16px gap
    var VISIBLE   = 4;   // cards visible at once on desktop

    function getMaxCatOffset() {
        if (!wrapper) return 0;
        var count = wrapper.children.length;
        if (count <= VISIBLE) return 0;
        return -((count - VISIBLE) * CARD_W);
    }

    function updateCatSlider() {
        if (!wrapper) return;
        wrapper.style.transform = 'translateX(' + catOffset + 'px)';
    }

    window.nextSlide = function () {
        if (!wrapper) return;
        catOffset -= CARD_W;
        if (catOffset < getMaxCatOffset()) catOffset = 0;
        updateCatSlider();
    };

    window.prevSlide = function () {
        if (!wrapper) return;
        catOffset += CARD_W;
        if (catOffset > 0) catOffset = getMaxCatOffset();
        updateCatSlider();
    };


    /* ═══════════════════════════════════════════════════════════
       HERO SLIDER  (publication / banner images)
    ═══════════════════════════════════════════════════════════ */
    var slider     = document.getElementById('slider');
    var pubIndex   = 0;

    function getSlideCount() {
        return slider ? slider.children.length : 0;
    }

    function updatePubSlider() {
        if (!slider) return;
        slider.style.transform = 'translateX(-' + (pubIndex * 100) + '%)';
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
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

});