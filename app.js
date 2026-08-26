// assets/js/app.js - Smart Expense Tracker Frontend Logic

document.addEventListener('DOMContentLoaded', () => {
    initModals();
    initSmartCategoryPredictor();
    initMobileNavigation();
    initNotifications();
    initTheme();
});

// Theme Switcher Handler (Dark / Light Mode)
function initTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        document.body.classList.add('light-mode');
    }

    const themeToggleBtns = document.querySelectorAll('.theme-toggle-btn');
    themeToggleBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            document.body.classList.toggle('light-mode');
            const isLight = document.body.classList.contains('light-mode');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
            showToast('Theme Updated', `Switched to ${isLight ? 'Light' : 'Dark'} mode.`);
        });
    });
}

// Web Audio Haptic Sound Synthesizer (Cha-Ching / Pop Sound)
function playSuccessSound() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.1); // A5

        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.start();
        osc.stop(ctx.currentTime + 0.3);
    } catch (e) {}
}

// Interactive Toast Pop Notifications Builder
function showToast(title, message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-pop toast-${type}`;

    const iconName = type === 'success' ? 'check-circle' : (type === 'danger' ? 'alert-triangle' : 'info');

    toast.innerHTML = `
        <div class="toast-icon-wrapper">
            <i data-lucide="${iconName}" style="width:20px; height:20px;"></i>
        </div>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-msg">${message}</div>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        <div class="toast-progress"></div>
    `;

    container.appendChild(toast);
    playSuccessSound();

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}


// Notifications Bell Drawer Toggle & Clear All Handler
function initNotifications() {
    const notifBtn = document.getElementById('notifBellBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const clearBtn = document.getElementById('clearNotifsBtn');
    const notifBadge = document.querySelector('.notif-badge');
    const notifList = document.querySelector('.notif-list');
    const notifCountBadge = document.getElementById('notifCountBadge');

    const originalListHTML = notifList ? notifList.innerHTML : '';
    const originalCount = notifBadge ? notifBadge.textContent : '0';

    if (sessionStorage.getItem('notifs_cleared') === 'true') {
        if (notifBadge) notifBadge.style.display = 'none';
        if (notifCountBadge) notifCountBadge.textContent = '0 Active';
        if (clearBtn) clearBtn.textContent = 'Restore';
        if (notifList) {
            notifList.innerHTML = `
                <div style="text-align:center; padding:32px 16px; color:var(--text-muted);">
                    <i data-lucide="check-circle-2" style="width:36px; height:36px; margin-bottom:8px; color:var(--success); opacity:0.8;"></i>
                    <div style="font-weight:700; color:var(--text-primary); font-size:0.9rem;">All caught up!</div>
                    <div style="font-size:0.78rem; margin-top:4px;">No active notifications right now. Click "Restore" to view again.</div>
                </div>
            `;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target) && e.target !== notifBtn) {
                notifDropdown.classList.remove('active');
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isCleared = sessionStorage.getItem('notifs_cleared') === 'true';

            if (isCleared) {
                // Restore Notifications
                sessionStorage.removeItem('notifs_cleared');
                if (notifBadge) notifBadge.style.display = 'flex';
                if (notifCountBadge) notifCountBadge.textContent = `${originalCount} Active`;
                if (clearBtn) clearBtn.textContent = 'Clear All';
                if (notifList) notifList.innerHTML = originalListHTML;
                if (typeof lucide !== 'undefined') lucide.createIcons();
                if (typeof showToast === 'function') {
                    showToast('Notifications Restored', 'Active notifications restored.');
                }
            } else {
                // Clear Notifications
                sessionStorage.setItem('notifs_cleared', 'true');
                if (notifBadge) notifBadge.style.display = 'none';
                if (notifCountBadge) notifCountBadge.textContent = '0 Active';
                if (clearBtn) clearBtn.textContent = 'Restore';
                if (notifList) {
                    notifList.innerHTML = `
                        <div style="text-align:center; padding:32px 16px; color:var(--text-muted);">
                            <i data-lucide="check-circle-2" style="width:36px; height:36px; margin-bottom:8px; color:var(--success); opacity:0.8;"></i>
                            <div style="font-weight:700; color:var(--text-primary); font-size:0.9rem;">All caught up!</div>
                            <div style="font-size:0.78rem; margin-top:4px;">No active notifications right now. Click "Restore" to view again.</div>
                        </div>
                    `;
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
                if (typeof showToast === 'function') {
                    showToast('Notifications Cleared', 'Notifications cleared.');
                }
            }
        });
    }
}


// Mobile Off-Canvas Sidebar Navigation
function initMobileNavigation() {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebarNav = document.getElementById('sidebarNav');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (hamburgerBtn && sidebarNav && sidebarOverlay) {
        function openSidebar() {
            sidebarNav.classList.add('active');
            sidebarOverlay.classList.add('active');
        }

        function closeSidebar() {
            sidebarNav.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        }

        hamburgerBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sidebarNav.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        sidebarOverlay.addEventListener('click', closeSidebar);

        // Auto close mobile menu on nav click
        const navLinks = sidebarNav.querySelectorAll('.nav-item a');
        navLinks.forEach(link => {
            link.addEventListener('click', closeSidebar);
        });
    }
}

// Modal Logic with Event Delegation
function initModals() {
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-modal-target]');
        if (trigger) {
            e.preventDefault();
            const targetId = trigger.getAttribute('data-modal-target');
            const modal = document.getElementById(targetId);
            if (modal) {
                modal.classList.add('active');
            }
        }

        const closeBtn = e.target.closest('[data-modal-close]');
        if (closeBtn) {
            const modal = closeBtn.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('active');
            }
        }
    });

    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });
}

// Smart Auto-Categorization Predictor
function initSmartCategoryPredictor() {
    const descInputs = document.querySelectorAll('.smart-desc-input');
    
    // Keyword dictionary mapping
    const rules = [
        { keywords: ['salary', 'paycheck', 'payroll', 'stipend', 'employer'], type: 'income', category: 'Salary' },
        { keywords: ['freelance', 'upwork', 'fiverr', 'contract', 'consulting'], type: 'income', category: 'Freelance & Side Hustle' },
        { keywords: ['dividend', 'stock', 'crypto', 'interest', 'investment'], type: 'income', category: 'Investments & Dividends' },
        { keywords: ['uber', 'lyft', 'gas', 'petrol', 'diesel', 'metro', 'bus', 'flight', 'train', 'cab', 'parking'], type: 'expense', category: 'Transportation & Gas' },
        { keywords: ['starbucks', 'mcdonalds', 'kfc', 'burger', 'pizza', 'restaurant', 'cafe', 'dinner', 'lunch', 'food', 'snack', 'grocery', 'walmart', 'whole foods', 'supermarket'], type: 'expense', category: 'Food & Dining' },
        { keywords: ['rent', 'lease', 'mortgage', 'apartment'], type: 'expense', category: 'Housing & Rent' },
        { keywords: ['netflix', 'spotify', 'hbo', 'disney', 'cinema', 'movie', 'game', 'steam', 'playstation', 'concert', 'ticket'], type: 'expense', category: 'Entertainment & Subscriptions' },
        { keywords: ['electricity', 'water', 'internet', 'wifi', 'bill', 'phone', 'telecom'], type: 'expense', category: 'Utilities & Bills' },
        { keywords: ['amazon', 'clothes', 'shoes', 'fashion', 'mall', 'shopping', 'zara', 'h&m'], type: 'expense', category: 'Shopping & Clothes' },
        { keywords: ['pharmacy', 'doctor', 'hospital', 'medicine', 'gym', 'fitness'], type: 'expense', category: 'Healthcare & Fitness' },
        { keywords: ['course', 'udemy', 'book', 'tuition', 'school', 'university'], type: 'expense', category: 'Education & Learning' }
    ];

    descInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            const val = e.target.value.toLowerCase().trim();
            if (val.length < 2) return;

            const form = input.closest('form');
            if (!form) return;

            const typeSelect = form.querySelector('select[name="type"]');
            const categorySelect = form.querySelector('select[name="category_id"]');

            if (!typeSelect || !categorySelect) return;

            for (const rule of rules) {
                const match = rule.keywords.some(kw => val.includes(kw));
                if (match) {
                    typeSelect.value = rule.type;
                    
                    // Match category select option by text content
                    Array.from(categorySelect.options).forEach(opt => {
                        if (opt.text.toLowerCase().includes(rule.category.toLowerCase())) {
                            categorySelect.value = opt.value;
                        }
                    });

                    // Highlight indicator
                    input.style.borderColor = '#10b981';
                    setTimeout(() => input.style.borderColor = '', 1500);
                    break;
                }
            }
        });
    });
}
