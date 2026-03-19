import axios from '@/lib/axios';
import { awbs } from '@/routes/slips';
import type { ColumnDefinition, ColumnComponent } from 'tabulator-tables';
import { formatCurrency, formatWeight, formatDate, formatTipObject, formatDistance } from '@/lib/formatters';
import type { AwbData, TabulatorDataResponse as DataResponse} from '@/types';

// Load data with pagination
export const loadData = async (params: {
    id: number;
    page?: number | 1;
    rows?: number | 100;
    sort?: Array<{column?: ColumnComponent; field: string; dir: 'asc' | 'desc'}>;
}): Promise<DataResponse<AwbData> | null> => {
    console.log('Loading data with pagination...', params);

    try {
        const urlParams = new URLSearchParams();

        urlParams.append('swapped', '0');
        // Add pagination parameters
        if (params.page !== undefined) {
            urlParams.append('page', params.page.toString());
        }
        if (params.rows !== undefined) {
            urlParams.append('rows', params.rows.toString());
        }

        // Add sorting parameters (use first sort if multiple)
        if (params.sort && params.sort.length > 0) {
            const firstSort = params.sort[0];
            urlParams.append('sortField', firstSort.field);
            urlParams.append('sortOrder', firstSort.dir);
        }

        const response = await axios.get(awbs(params.id).url + '?' + urlParams.toString());
        const result = response.data;

        if (result.success && result.data && 'data' in result.data) {
            console.log('Data loaded:', result.data.data?.length || 0, 'records, page:', result.data.current_page || 'N/A');
            return result;
        }
    } catch (error) {
        console.error('Error loading data:', error);
    }

    return null;
};

// Tabulator column definitions
export const baseColumns = (): ColumnDefinition[] => [
    {
        title: "#",
        field: "rownum",
        headerHozAlign: "center",
        formatter: "rownum",
        hozAlign:"center",
        width: 30,
        frozen: true,
        headerSort: false
    },
    {
        title: "AWB",
        field: "awb",
        widthGrow:1,
        frozen: true,
        formatter: (cell) => `<strong>${cell.getValue()}</strong>`
    },
    {
        title: "Expeditor",
        field: "expeditor_nume",
        widthGrow:1,
    },
    {
        title: "Localitate",
        field: "expeditor_localitate",
        widthGrow:1,
    },
    {
        title: "Destinatar",
        field: "destinatar_nume",
        widthGrow:1,
    },
    {
        title: "Localitate",
        field: "destinatar_localitate",
        widthGrow:1,
    },
    {
        title: "Tip",
        field: "tip_obj",
        headerHozAlign: "center",
        widthGrow:1,
        hozAlign:"center",
        formatter: (cell) => {
            const value = cell.getValue();
            const { label, colorClass } = formatTipObject(value);
            return `<span class="px-2 py-1 text-xs rounded-full ${colorClass}">${label}</span>`;
        }
    },
    {
        title: "Piese",
        field: "piese",
        headerHozAlign: "center",
        widthGrow:1,
        hozAlign: "center"
    },
    {
        title: "Greutate",
        field: "greutate",
        headerHozAlign: "center",
        widthGrow:1,
        formatter: (cell) => formatWeight(cell.getValue()),
        hozAlign: "right"
    },
    {
        title: "Ramburs",
        field: "ramburs",
        headerHozAlign: "center",
        widthGrow:1,
        formatter: (cell) => formatCurrency(cell.getValue()),
        hozAlign: "right"
    },
    {
        title: "Nr. Km",
        field: "km_exteriori",
        headerHozAlign: "right",
        widthGrow:1,
        formatter: (cell) => formatDistance(cell.getValue()),
        hozAlign: "right"
    },
    {
        title: "Valoare",
        field: "valoare_fara_tva",
        headerHozAlign: "right",
        widthGrow:1,
        formatter: (cell) => formatCurrency(cell.getValue()),
        hozAlign: "right"
    },
    {
        title: "TVA",
        field: "valoare_tva",
        headerHozAlign: "right",
        widthGrow:1,
        formatter: (cell) => formatCurrency(cell.getValue()),
        hozAlign: "right"
    },
    {
        title: "Data",
        field: "data_expeditie",
        headerHozAlign: "center",
        widthGrow:1,
        formatter: (cell) => formatDate(cell.getValue()),
        hozAlign: "center"
    }
];

// Actions column factory function
export const actionsColumns = (handlers: {
    onPrint: (rowIds: number[]) => void;
}): ColumnDefinition => {
    return {
        title: "Actiuni",
        field: "actions",
        width: 100,
        headerSort: false,
        frozen: true,
        formatter: () => {
            return `
                <div class="tabulator-cell-actions">
                    <button class="print-btn btn btn-primary" title="Print">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>
                </div>
            `;
        },
        cellClick: (e, cell) => {
            const target = e.target as HTMLElement;
            const rowData = cell.getRow().getData() as AwbData;

            if (target.closest('.print-btn')) {
                handlers.onPrint([rowData.id as number]);
            }
        }
    };
};
