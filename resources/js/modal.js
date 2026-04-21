// Cleopatra-style modal (adapted for Vite)

export function initModal() {
    document.querySelectorAll('[data-modal-target]').forEach((trigger) => {
        if (trigger.hasAttribute('data-modal-init')) {
            return;
        }
        trigger.setAttribute('data-modal-init', 'true');

        trigger.addEventListener('click', () => {
            const targetId = trigger.getAttribute('data-modal-target');
            if (targetId) {
                openModal(targetId);
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((btn) => {
        if (btn.hasAttribute('data-modal-close-init')) {
            return;
        }
        btn.setAttribute('data-modal-close-init', 'true');

        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if (modal?.id) {
                closeModal(modal.id);
            }
        });
    });

    document.querySelectorAll('.modal').forEach((modal) => {
        if (modal.hasAttribute('data-modal-backdrop-init')) {
            return;
        }
        modal.setAttribute('data-modal-backdrop-init', 'true');

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const openModalEl = document.querySelector('.modal.is-open');
            if (openModalEl) {
                closeModal(openModalEl.id);
            }
        }
    });
}

export function openModal(id) {
    const modal = document.getElementById(id);
    const backdrop =
        document.querySelector(`[data-modal-backdrop="${id}"]`) ||
        document.querySelector('.modal-backdrop');

    if (!modal) {
        return;
    }

    document.body.style.overflow = 'hidden';
    if (backdrop) {
        backdrop.classList.add('is-open');
    }
    modal.classList.add('is-open');
}

export function closeModal(id) {
    const modal = document.getElementById(id);
    const backdrop =
        document.querySelector(`[data-modal-backdrop="${id}"]`) ||
        document.querySelector('.modal-backdrop');

    if (!modal) {
        return;
    }

    modal.classList.remove('is-open');
    if (backdrop) {
        backdrop.classList.remove('is-open');
    }
    document.body.style.overflow = '';
}

window.openModal = openModal;
window.closeModal = closeModal;
