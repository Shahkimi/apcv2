/**
 * Shared DataTables options — Cleopatra user-management layout (toolbar + footer).
 */
export const kawalanDataTableDefaults = {
    dom:
        '<"kawalan-dt-toolbar px-4 pt-4 pb-2"<"flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"l f>>rt<"kawalan-dt-footer flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-border bg-muted/30 px-4 py-3 dark:bg-muted/10"ip>',
    autoWidth: false,
    pageLength: 10,
    lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100],
    ],
};
