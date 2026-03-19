/**
 * Currency formatter for Romanian locale
 */
export const formatCurrency = (value: number | string | undefined): string => {
    if (value === undefined || value === null) value = 'N/A';
    const numericValue = Number(value);
    if (Number.isNaN(numericValue)) value = 'N/A';

    return new Intl.NumberFormat('ro-RO', {
        style: 'currency',
        currency: 'Lei',
        currencyDisplay: 'code', // shows Lei
    }).format(numericValue);
};

/**
 * Weight formatter with kg suffix
 */
export const formatWeight = (weight: number | string | null | undefined): string => {
    if (weight === null || weight === undefined) return 'N/A';
    const numericWeight = Number(weight);
    if (Number.isNaN(numericWeight)) return 'N/A';
    return `${numericWeight.toFixed(2)} kg`;
};

/**
 * Distance formatter with km suffix
 */
export const formatDistance = (distance: number | string | null | undefined): string => {
    if (distance === null || distance === undefined) return 'N/A';
    const numericDistance = Number(distance);
    if (Number.isNaN(numericDistance)) return 'N/A';
    return `${numericDistance.toFixed(2)} km`;
};

/**
 * Date formatter for Romanian locale
 */
export const formatDate = (dateString: string | undefined | null): string => {
    if (dateString == undefined || dateString == null || dateString == '' 
        || dateString == '0000-00-00' || dateString == '1970-01-01' 
        || dateString == '1970-01-01 00:00:00' || dateString == '0000-00-00 00:00:00') return '-';
    return new Date(dateString).toLocaleDateString('ro-RO');
};

/**
 * Tip object formatter with label and styling
 */
export const formatTipObject = (tipObject: 1 | 2 | 3): { label: string; colorClass: string } => {
    const tipObjectOptions = [
        { label: 'Plic', value: 1, colorClass: 'bg-blue-100 text-blue-800' },
        { label: 'Colet', value: 2, colorClass: 'bg-green-100 text-green-800' },
        { label: 'Palet', value: 3, colorClass: 'bg-orange-100 text-orange-800' }
    ];

    const option = tipObjectOptions.find(opt => opt.value === tipObject);
    return {
        label: option?.label || `${tipObject}`,
        colorClass: option?.colorClass || 'bg-gray-100 text-gray-800'
    };
};

export const formatTipPlata = (tipPlata: number): string => {
    const tipPlataOptions: Record<number, string> = {
        0: 'cash',
        1: 'bon de ordine',
        2: 'cec',
        3: 'cont colector'
    };
    return tipPlataOptions[tipPlata] || `${tipPlata}`;
};

/**
 * Tip retur formatter with label and styling
 */
export const formatTipAwb = (tip: number | undefined | null): { label: string; colorClass: string } => {
    const tipReturOptions = [
        { label: 'Initiala', value: 0, colorClass: 'bg-gray-100 text-gray-800' },
        { label: 'Retur NT', value: 1, colorClass: 'bg-blue-100 text-blue-800' },
        { label: 'Retur doc.', value: 2, colorClass: 'bg-green-100 text-green-800' },
        { label: 'Ramburs', value: 3, colorClass: 'bg-orange-100 text-orange-800' },
        { label: 'Returnare', value: 5, colorClass: 'bg-red-100 text-purple-800' },
        { label: 'Retur Amb.', value: 6, colorClass: 'bg-yellow-100 text-yellow-800' },
        { label: 'Retur Colet', value: 7, colorClass: 'bg-purple-100 text-red-800' }
    ];

    const option = tipReturOptions.find(opt => opt.value === tip);
    return {
        label: option?.label || `${tip}`,
        colorClass: option?.colorClass || 'bg-gray-100 text-gray-800'
    };
};

/**
 * Status comanda formatter with label and styling
 */
export const formatStatusComanda = (status: 1 | 2 | 3 | 4 | 5 | 6 | 7): { label: string; colorClass: string } => {
    const statusMap: Record<number, string> = {
        1: 'Initiala',
        2: 'Transmisa',
        3: 'Distribuita',
        4: 'Acceptata',
        5: 'Refuzata',
        6: 'Colectata',
        7: 'Anulata'
    };
    const statusColors: Record<string, string> = {
        'Initiala': 'bg-blue-100 text-blue-800',
        'Transmisa': 'bg-purple-100 text-purple-800',
        'Distribuita': 'bg-indigo-100 text-indigo-800',
        'Acceptata': 'bg-green-100 text-green-800',
        'Refuzata': 'bg-red-100 text-red-800',
        'Colectata': 'bg-emerald-100 text-emerald-800',
        'Anulata': 'bg-gray-100 text-gray-800'
    };
    const statusText = statusMap[status] || status.toString();
    const colorClass = statusColors[statusText] || 'bg-gray-100 text-gray-800';
    return {
        label: statusText,
        colorClass: colorClass
    };
};

/**
 * Status comanda HTML formatter for Tabulator cells
 */
export const formatStatusComandaHtml = (status: 1 | 2 | 3 | 4 | 5 | 6 | 7): string => {
    const { label, colorClass } = formatStatusComanda(status);
    return `<span class="px-2 py-1 rounded-full text-xs font-medium ${colorClass}">${label}</span>`;
};

export const formatLocalitate = (judet: string | null | undefined, localitate: string | null | undefined): string => {
    if (!localitate) return '';
    return localitate.charAt(0).toUpperCase() + localitate.slice(1).toLowerCase() + (judet ? ` (${judet})` : '');
};

