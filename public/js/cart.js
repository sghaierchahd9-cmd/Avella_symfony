/**
 * js/cart.js  —  Avella Cart
 *
 * Loaded only for logged-in users.
 * All fetch calls are built from dir() which resolves to the
 * current page's folder — always pages/buyer/ for buyer pages.
 */

(() => {
    'use strict';

    /* ── Resolve current page directory ──────────────────────────
       /pages/buyer/buyer-dashboard.php → /pages/buyer/
       So fetch(dir() + 'add_to_cart.php') = /pages/buyer/add_to_cart.php ✓
    ─────────────────────────────────────────────────────────── */
    function dir() {
        const parts = window.location.pathname.split('/');
        parts.pop(); // remove last part (file name)
        parts[parts.length - 1] = '';
        return parts.join('/');
    }

    /* ── DOM refs (all use avella- prefixed IDs) ─────────────── */
    let overlay, modal, cartBody, cartFooter,
        confirmBtn, cancelBtn,
        confirmDialog, confirmOkBtn, confirmCancelBtn,
        toast, toastTimer, navBadge;

    /* ── Init ─────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', () => {
        overlay          = document.getElementById('avella-cart-overlay');
        modal            = document.getElementById('avella-cart-modal');
        cartBody         = document.getElementById('avella-cart-body');
        cartFooter       = document.getElementById('avella-cart-footer');
        confirmDialog    = document.getElementById('avella-confirm-dialog');
        confirmOkBtn     = document.getElementById('avella-confirm-ok');
        confirmCancelBtn = document.getElementById('avella-confirm-cancel');
        toast            = document.getElementById('avella-cart-toast');
        navBadge         = document.getElementById('navbar-cart-count');

        /* Cart open button */
        const openBtn = document.getElementById('cart-open-btn');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                refreshCartBody().then(openCart);
            });
        }

        /* Cart close button */
        const closeBtn = document.getElementById('cart-close-btn');
        if (closeBtn) closeBtn.addEventListener('click', closeCart);

        /* Click overlay to close */
        if (overlay) {
            overlay.addEventListener('click', e => {
                if (e.target === overlay) closeCart();
            });
        }

        /* ESC key */
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                if (confirmDialog && confirmDialog.classList.contains('open')) {
                    closeConfirmDialog();
                } else {
                    closeCart();
                }
            }
        });

        /* Delegated events on cart body */
        if (cartBody) cartBody.addEventListener('click', handleCartBodyClick);

        /* Footer buttons */
        wireFooterButtons();

        /* Confirm dialog ok/cancel */
        if (confirmOkBtn)     confirmOkBtn.addEventListener('click', handleConfirmOrder);
        if (confirmCancelBtn) confirmCancelBtn.addEventListener('click', closeConfirmDialog);

        /* Wire all add-to-cart buttons already in DOM */
        wireAddToCartButtons();
    });

    /* ── Open / close ─────────────────────────────────────────── */
    function openCart() {
        if (!overlay) return;
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function closeCart() {
        if (!overlay) return;
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    /* ── Confirm dialog ───────────────────────────────────────── */
    function openConfirmDialog()  {
        if (confirmDialog) {
            confirmDialog.classList.add('open');
            confirmDialog.setAttribute('aria-hidden', 'false');
        }
    }
    function closeConfirmDialog() {
        if (confirmDialog) {
            confirmDialog.classList.remove('open');
            confirmDialog.setAttribute('aria-hidden', 'true');
        }
    }

    /* ── Toast ────────────────────────────────────────────────── */
    function showToast(msg, type = 'success', dur = 3000) {
        if (!toast) return;
        clearTimeout(toastTimer);
        toast.textContent = (type === 'success' ? '✓  ' : '✕  ') + msg;
        toast.classList.add('show');
        toastTimer = setTimeout(() => toast.classList.remove('show'), dur);
    }

    /* ── Navbar badge ─────────────────────────────────────────── */
    function updateBadge(count) {
        if (!navBadge) return;
        const n = parseInt(count, 10) || 0;
        navBadge.textContent = n;
        if (n > 0) {
            navBadge.classList.remove('hidden');
        } else {
            navBadge.classList.add('hidden');
        }
    }

    /* ── Refresh cart body ────────────────────────────────────── */
    function refreshCartBody() {
        return fetch(window.AVELLA_ROUTES.cartFragment, { credentials: 'same-origin' })
            .then(r => r.text())
            .then(html => {
                if (!cartBody) return;
                cartBody.innerHTML = html;

                const hasItems = !!cartBody.querySelector('.avella-cart-item');

                if (hasItems) {
                    const cidEl = cartBody.querySelector('#avella-commande-id');
                    const cid   = cidEl ? cidEl.dataset.commandeId : '';

                    /* Build footer if missing */
                    if (!cartFooter) {
                        cartFooter = document.createElement('div');
                        cartFooter.id = 'avella-cart-footer';
                        if (modal) modal.appendChild(cartFooter);
                    }

                    cartFooter.innerHTML = `
                        <div class="avella-cart-totals">
                            <span>Total</span>
                            <span id="avella-cart-total">0.00 TND</span>
                        </div>
                        <div class="avella-cart-actions">
                            <button id="avella-cancel-btn" class="avella-btn-ghost"
                                    data-commande-id="${cid}">Vider le panier</button>
                            <button id="avella-confirm-btn" class="avella-btn-primary"
                                    data-commande-id="${cid}">Confirmer la commande</button>
                        </div>`;

                    /* Recalculate total from rendered subtotals */
                    let total = 0;
                    cartBody.querySelectorAll('.avella-cart-item-sub').forEach(el => {
                        total += parseFloat(el.textContent) || 0;
                    });
                    const totalEl = document.getElementById('avella-cart-total');
                    if (totalEl) totalEl.textContent = total.toFixed(2) + ' TND';

                    cartFooter.style.display = 'flex';
                    wireFooterButtons();
                    confirmOkBtn     = document.getElementById('avella-confirm-ok');
                    confirmCancelBtn = document.getElementById('avella-confirm-cancel');
                    if (confirmOkBtn)     confirmOkBtn.addEventListener('click', handleConfirmOrder);
                    if (confirmCancelBtn) confirmCancelBtn.addEventListener('click', closeConfirmDialog);
                } else {
                    if (cartFooter) cartFooter.style.display = 'none';
                }
            })
            .catch(() => {});
    }

    /* ── Wire footer buttons ──────────────────────────────────── */
    function wireFooterButtons() {
        /* Clone to remove any stale listeners */
        const cb = document.getElementById('avella-confirm-btn');
        if (cb) {
            const fresh = cb.cloneNode(true);
            cb.replaceWith(fresh);
            fresh.addEventListener('click', openConfirmDialog);
            confirmBtn = fresh;
        }
        const vb = document.getElementById('avella-cancel-btn');
        if (vb) {
            const fresh = vb.cloneNode(true);
            vb.replaceWith(fresh);
            fresh.addEventListener('click', handleCancelCart);
            cancelBtn = fresh;
        }
    }

    /* ── Add to cart (called from any page) ───────────────────── */
    function addToCart(produitId, qty) {
        qty = qty || 1;

        if (!produitId || produitId <= 0) {
            showToast('Produit non disponible.', 'error');
            return;
        }

        /* Disable all buttons for this product */
        const btns = document.querySelectorAll(
            '.avella-add-to-cart[data-produit-id="' + produitId + '"]'
        );
        btns.forEach(b => {
            b.disabled = true;
            if (!b._orig) b._orig = b.innerHTML;
            b.innerHTML = '⏳ Ajout...';
        });

        fetch(window.AVELLA_ROUTES.addToCart, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'produit_id=' + encodeURIComponent(produitId) + '&quantite=' + encodeURIComponent(qty),
            credentials: 'same-origin',
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Article ajouté !', 'success');
                    updateBadge(data.cart_count);
                    refreshCartBody().then(openCart);
                } else {
                    showToast(data.message || 'Erreur.', 'error');
                    if (data.redirect) {
                        setTimeout(() => { window.location.href = data.redirect; }, 1200);
                    }
                }
            })
            .catch(() => showToast('Erreur réseau.', 'error'))
            .finally(() => {
                btns.forEach(b => {
                    b.disabled = false;
                    b.innerHTML = b._orig || 'Ajouter au panier';
                });
            });
    }

    /* ── Cart body click handler ──────────────────────────────── */
    function handleCartBodyClick(e) {
        const qtyBtn = e.target.closest('.avella-qty-btn');
        if (qtyBtn) {
            updateItem(
                parseInt(qtyBtn.dataset.cpId, 10),
                qtyBtn.dataset.action,
                qtyBtn
            );
            return;
        }
        const removeBtn = e.target.closest('.avella-cart-remove');
        if (removeBtn) {
            updateItem(
                parseInt(removeBtn.dataset.cpId, 10),
                'remove',
                removeBtn
            );
        }
    }

    /* ── Update / remove item ─────────────────────────────────── */
    function updateItem(cpId, action, triggerEl) {
        const row  = triggerEl && triggerEl.closest('.avella-cart-item');
        const btns = row && row.querySelectorAll('button');
        if (row)  row.style.opacity = '0.5';
        if (btns) btns.forEach(b => b.disabled = true);

        fetch(window.AVELLA_ROUTES.updateCart, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'cp_id=' + encodeURIComponent(cpId) + '&action=' + encodeURIComponent(action),
            credentials: 'same-origin',
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    showToast(data.message || 'Erreur.', 'error');
                    if (row)  row.style.opacity = '1';
                    if (btns) btns.forEach(b => b.disabled = false);
                    return;
                }

                updateBadge(data.cart_count);

                if (data.removed) {
                    if (row) {
                        row.style.transition = 'opacity .22s, transform .22s';
                        row.style.opacity    = '0';
                        row.style.transform  = 'translateX(14px)';
                        setTimeout(() => {
                            row.remove();
                            if (data.cart_empty) {
                                refreshCartBody();
                                if (cartFooter) cartFooter.style.display = 'none';
                            } else {
                                const tot = document.getElementById('avella-cart-total');
                                if (tot) tot.textContent = data.cart_total + ' TND';
                            }
                        }, 230);
                    }
                } else {
                    const qv = row && row.querySelector('.avella-qty-val');
                    if (qv) qv.textContent = data.item_qty;
                    row && row.querySelectorAll('.avella-qty-btn').forEach(b => {
                        b.dataset.qty = data.item_qty;
                    });
                    const sub = row && row.querySelector('.avella-cart-item-sub');
                    if (sub) sub.textContent = data.item_sub + ' TND';
                    if (row)  row.style.opacity = '1';
                    if (btns) btns.forEach(b => b.disabled = false);
                    const tot = document.getElementById('avella-cart-total');
                    if (tot) tot.textContent = data.cart_total + ' TND';
                }
            })
            .catch(() => {
                showToast('Erreur réseau.', 'error');
                if (row)  row.style.opacity = '1';
                if (btns) btns.forEach(b => b.disabled = false);
            });
    }

    /* ── Confirm order ────────────────────────────────────────── */
    function handleConfirmOrder() {
        const cid = (document.getElementById('avella-confirm-btn') || {}).dataset
            && document.getElementById('avella-confirm-btn').dataset.commandeId;
        if (!cid) { closeConfirmDialog(); return; }

        if (confirmOkBtn) { confirmOkBtn.disabled = true; confirmOkBtn.textContent = '...'; }

        fetch(window.AVELLA_ROUTES.confirmOrder, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'commande_id=' + encodeURIComponent(cid),
            credentials: 'same-origin',
        })
            .then(r => r.json())
            .then(data => {
                closeConfirmDialog();
                if (data.success) {
                    showToast(data.message || 'Commande confirmée !', 'success', 4000);
                    updateBadge(0);
                    refreshCartBody();
                    if (cartFooter) cartFooter.style.display = 'none';
                    setTimeout(closeCart, 2500);
                } else {
                    showToast(data.message || 'Erreur.', 'error');
                }
            })
            .catch(() => showToast('Erreur réseau.', 'error'))
            .finally(() => {
                if (confirmOkBtn) {
                    confirmOkBtn.disabled = false;
                    confirmOkBtn.textContent = 'Confirmer';
                }
            });
    }

    /* ── Cancel / empty cart ──────────────────────────────────── */
    function handleCancelCart() {
        const btn = document.getElementById('avella-cancel-btn');
        const cid = btn && btn.dataset.commandeId;
        if (!cid) return;
        if (!window.confirm('Vider votre panier ? Cette action est irréversible.')) return;

        fetch(dir() + 'buyer/cancel-cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'commande_id=' + encodeURIComponent(cid),
            credentials: 'same-origin',
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Panier vidé.', 'success');
                    updateBadge(0);
                    refreshCartBody();
                    if (cartFooter) cartFooter.style.display = 'none';
                } else {
                    showToast(data.message || 'Erreur.', 'error');
                }
            })
            .catch(() => showToast('Erreur réseau.', 'error'));
    }

    /* ── Wire .avella-add-to-cart buttons ─────────────────────── */
    function wireAddToCartButtons() {
        document.querySelectorAll('.avella-add-to-cart').forEach(btn => {
            if (btn.dataset.wired) return;
            btn.dataset.wired = '1';
            btn.addEventListener('click', () => {
                addToCart(
                    parseInt(btn.dataset.produitId, 10),
                    parseInt(btn.dataset.qty || '1', 10)
                );
            });
        });
    }

    /* ── Public API ───────────────────────────────────────────── */
    window.AvellaCart = {
        add:         addToCart,
        open:        openCart,
        close:       closeCart,
        refresh:     refreshCartBody,
        wire:        wireAddToCartButtons,
        toast:       showToast,
    };

})();