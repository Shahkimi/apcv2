import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

/**
 * Delete confirmation for Kawalan DataTables actions (replaces window.confirm).
 *
 * @param {{ title?: string, text?: string, confirmButtonText?: string, cancelButtonText?: string }} [options]
 * @returns {Promise<boolean>} true if the user confirmed
 */
export function kawalanConfirmDelete(options = {}) {
    const title = options.title ?? '';
    const text = options.text ?? '';
    const confirmButtonText = options.confirmButtonText ?? 'Padam';
    const cancelButtonText = options.cancelButtonText ?? 'Batal';

    return Swal.fire({
        title,
        ...(text ? { text } : {}),
        icon: 'warning',
        showCancelButton: true,
        focusCancel: true,
        reverseButtons: true,
        confirmButtonText,
        cancelButtonText,
        background: 'var(--popover)',
        color: 'var(--popover-foreground)',
        buttonsStyling: false,
        customClass: {
            popup: 'kawalan-swal2-popup',
            htmlContainer: 'kawalan-swal2-text',
            actions: 'kawalan-swal2-actions',
            confirmButton: 'kawalan-swal2-confirm',
            cancelButton: 'kawalan-swal2-cancel',
        },
    }).then((result) => result.isConfirmed === true);
}

window.Swal = Swal;
window.kawalanConfirmDelete = kawalanConfirmDelete;
