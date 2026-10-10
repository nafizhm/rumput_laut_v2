// Display Indonesian separators while keeping canonical decimal strings in forms.
export function formatMoneyInput(value) {
    if (value === '' || value === null || value === undefined) return '';
    const [whole, fraction] = String(value).split('.');
    const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return grouped + (fraction === undefined ? '' : ',' + fraction);
}

export function parseMoneyInput(value, decimals = 0) {
    const clean = String(value).replace(/\./g, '').replace(/[^0-9,]/g, '');
    const [whole, ...fractions] = clean.split(',');
    const integer = whole.replace(/^0+(?=\d)/, '');
    if (decimals > 0 && fractions.length) {
        return (integer || '0') + '.' + fractions.join('').slice(0, decimals);
    }
    return integer;
}

export function moneyInput(initial = '', decimals = 0) {
    return {
        internalValue: initial ?? '',
        get displayValue() { return formatMoneyInput(this.internalValue); },
        update(event) {
            const el = event.target;
            const count = el.value.slice(0, el.selectionStart).replace(/[^0-9,]/g, '').length;
            this.internalValue = parseMoneyInput(el.value, decimals);
            el.value = this.displayValue;
            let cursor = 0;
            let found = 0;
            while (cursor < el.value.length && found < count) {
                if (/[0-9,]/.test(el.value[cursor])) found++;
                cursor++;
            }
            el.setSelectionRange(cursor, cursor);
        },
    };
}
