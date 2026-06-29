document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('.gdi-results-table');

    if (!table) {
        return;
    }

    const tbody = table.querySelector('tbody');
    const buttons = table.querySelectorAll('.gdi-sort-button');

    buttons.forEach((button, columnIndex) => {
        button.addEventListener('click', () => {
            const sortType = button.dataset.sort || 'text';
            const currentOrder = button.dataset.order === 'asc' ? 'desc' : 'asc';

            buttons.forEach((btn) => {
                btn.removeAttribute('data-order');
            });

            button.dataset.order = currentOrder;

            const rows = Array.from(tbody.querySelectorAll('tr'));

            rows.sort((a, b) => {
                const aText = a.children[columnIndex]?.textContent.trim() || '';
                const bText = b.children[columnIndex]?.textContent.trim() || '';

                if (sortType === 'date') {
                    return compareDates(aText, bText, currentOrder);
                }

                return compareText(aText, bText, currentOrder);
            });

            rows.forEach((row) => tbody.appendChild(row));
        });
    });

    function compareText(a, b, order) {
        const result = a.localeCompare(b, undefined, {
            numeric: true,
            sensitivity: 'base',
        });

        return order === 'asc' ? result : -result;
    }

    function compareDates(a, b, order) {
        const aDate = new Date(a).getTime() || 0;
        const bDate = new Date(b).getTime() || 0;
        const result = aDate - bDate;

        return order === 'asc' ? result : -result;
    }
});