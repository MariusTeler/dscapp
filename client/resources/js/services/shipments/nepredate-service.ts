import { nepredate } from '@/routes/shipments';
import type { ColumnDefinition, ColumnComponent } from 'tabulator-tables';
import { formatCurrency, formatWeight, formatDate, formatTipObject, formatDistance } from '@/lib/formatters';
import type { AwbData, TabulatorDataResponse as DataResponse} from '@/types';
import axios from '@/lib/axios';

// Load data with pagination
export const loadData = async (params: {
    page?: number;
    rows?: number;
    sort?: Array<{column?: ColumnComponent; field: string; dir: 'asc' | 'desc'}>;
    filters?: Array<{field: string; type: string; value: string | number | boolean | null | undefined}>;
    swapped?: boolean;
    startDate?: string;
    endDate?: string;
}): Promise<DataResponse<AwbData> | null> => {
    console.log('Loading data with pagination...', params);

    try {
        const urlParams = new URLSearchParams();

        urlParams.append('swapped', params.swapped ? '1' : '0');
        
        // Add date range parameters
        if (params.startDate) {
            urlParams.append('startDate', params.startDate);
        }
        if (params.endDate) {
            urlParams.append('endDate', params.endDate);
        }
        
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

        // Add filter parameters from Tabulator filters
        if (params.filters && Array.isArray(params.filters)) {
            params.filters.forEach((filter) => {
                const { field, value } = filter;

                // Skip empty or null values
                if (value === null || value === undefined || value === '') {
                    return;
                }

                // Convert filter to backend format
                if (field === 'tip_obj' && typeof value === 'number') {
                    urlParams.append(`filter_${field}`, value.toString());
                } else if (typeof value === 'string' && value.trim()) {
                    urlParams.append(`filter_${field}`, value.trim());
                } else if (typeof value === 'number') {
                    urlParams.append(`filter_${field}`, value.toString());
                }

                // Optional: Add filter type for more advanced filtering
                // urlParams.append(`filter_${field}_type`, type);
            });
        }

        const response = await axios.get(nepredate().url + '?' + urlParams.toString(), {
                headers: {
                    'Content-Type': 'application/json',
                },
            })

        if (response.data.success && response.data.data && 'data' in response.data.data) {
            console.log('Data loaded:', response.data.data.data?.length || 0, 'records, page:', response.data.data.current_page || 'N/A');
            return response.data;
        }
    } catch (error) {
        if (axios.isAxiosError(error)) {
            console.error('Failed to load data:', error.response?.statusText || error.message);
        } else {
            console.error('Error loading data:', error);
        }
        throw error;
    }

    return null;
};

// Tabulator column definitions
export const baseColumns = (): ColumnDefinition[] => [
    {
        title: "",
        field: "checkbox",
        formatter: "rowSelection",
        titleFormatter: "rowSelection",
        width: 60,
        headerHozAlign: "center",
        hozAlign: "center",
        resizable: false,
        headerSort: false,
        frozen:true, 
    },
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
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        formatter: (cell) => `<strong>${cell.getValue()}</strong>`
    },
    {
        title: "Expeditor",
        field: "expeditor_nume",
        widthGrow:1,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Localitate",
        field: "expeditor_localitate",
        widthGrow:1,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Destinatar",
        field: "destinatar_nume",
        widthGrow:1,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Localitate",
        field: "destinatar_localitate",
        widthGrow:1,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Tip",
        field: "tip_obj",
        headerHozAlign: "center",
        widthGrow:1,
        hozAlign:"center",
        headerFilter: "list",
        headerFilterParams: {
            values: {
                "": "...",
                "1": "Plic",
                "2": "Colet",
                "3": "Palet"
            }
        },
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
        //headerFilter: "input",
        //headerFilterPlaceholder: "...",
        formatter: (cell) => formatDate(cell.getValue()),
        hozAlign: "center"
    }
];

// Actions column factory function
export const actionsColumns = (handlers: {
    onEdit: (rowData: AwbData) => void;
    onPrint: (rowIds: number[]) => void;
    onDelete: (rowData: AwbData) => void;
}): ColumnDefinition => {
    return {
        title: "Actiuni",
        field: "actions",
        width: 100,
        headerSort: false,
        frozen: true,
        formatter: (cell) => {
            const rowData = cell.getRow().getData() as AwbData;
            
            const editButton = rowData.can_update ?? true ? `
                <button class="edit-btn btn btn-secondary" title="Edit">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
            ` : '';
            
            return `
                <div class="tabulator-cell-actions">
                    ${editButton}
                    <button class="print-btn btn btn-primary" title="Print">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>
                    <button class="delete-btn btn btn-destructive" title="Delete">
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            `;
        },
        cellClick: (e, cell) => {
            const target = e.target as HTMLElement;
            const rowData = cell.getRow().getData() as AwbData;

            if (target.closest('.edit-btn')) {
                handlers.onEdit(rowData);
            } else if (target.closest('.print-btn')) {
                handlers.onPrint([rowData.id as number]);
            } else if (target.closest('.delete-btn')) {
                handlers.onDelete(rowData);
            }
        }
    };
};
