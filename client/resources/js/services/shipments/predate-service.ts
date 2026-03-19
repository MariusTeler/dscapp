import { predate } from '@/routes/shipments';
import type { ColumnDefinition, ColumnComponent } from 'tabulator-tables';
import { formatCurrency, formatWeight, formatDate, formatTipObject } from '@/lib/formatters';
import type { AwbData, TabulatorDataResponse as DataResponse} from '@/types';
import axios from '@/lib/axios';

// Load data with pagination
export const loadData = async (params: {
    page?: number;
    rows?: number;
    sort?: Array<{column?: ColumnComponent; field: string; dir: 'asc' | 'desc'}>;
    filters?: Array<{field: string; type: string; value: string | number | boolean | null | undefined}>;
    startDate?: string;
    endDate?: string;
}): Promise<DataResponse<AwbData> | null> => {
    console.log('Loading data with pagination...', params);

    try {
        const urlParams = new URLSearchParams();

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
            });
        }

        const response = await axios.get(predate().url + '?' + urlParams.toString(), {
            headers: {
                'Content-Type': 'application/json',
            },
        });

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
        hozAlign: "center",
        width: 30,
        frozen: true,
        headerSort: false
    },
    {
        title: "AWB",
        field: "awb",
        widthGrow: 1,
        frozen: true,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        formatter: (cell) => `<strong>${cell.getValue()}</strong>`
    },
    {
        title: "Expeditor",
        field: "expeditor_nume",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Loc. Expeditor",
        field: "expeditor_localitate",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Destinatar",
        field: "destinatar_nume",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Loc. Destinatar",
        field: "destinatar_localitate",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Tip",
        field: "tip_obj",
        headerHozAlign: "center",
        widthGrow: 1,
        hozAlign: "center",
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
        widthGrow: 1,
        hozAlign: "center"
    },
    {
        title: "Greutate",
        field: "greutate",
        headerHozAlign: "center",
        widthGrow: 1,
        formatter: (cell) => formatWeight(cell.getValue()),
        hozAlign: "right"
    },
    {
        title: "Ramburs",
        field: "ramburs",
        headerHozAlign: "center",
        widthGrow: 1,
        formatter: (cell) => formatCurrency(parseFloat(cell.getValue())),
        hozAlign: "right"
    },
    {
        title: "Data Colectare",
        field: "data_expeditie",
        headerHozAlign: "center",
        widthGrow: 1,
        formatter: (cell) => formatDate(cell.getValue()),
        hozAlign: "center"
    },
    {
        title: "Status",
        field: "status",
        headerHozAlign: "center",
        widthGrow: 1,
        hozAlign: "center",
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Data Status",
        field: "data_status",
        headerHozAlign: "center",
        widthGrow: 1,
        formatter: (cell) => formatDate(cell.getValue()),
        hozAlign: "center"
    },
    {
        title: "Checkpoint",
        field: "ckp",
        headerHozAlign: "center",
        widthGrow: 1,
        hozAlign: "center",
        headerSort: false,
    },
    {
        title: "Centru",
        field: "centru_ckp",
        headerHozAlign: "center",
        widthGrow: 1,
        hozAlign: "center",
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Primitor",
        field: "primitor",
        headerHozAlign: "center",
        widthGrow: 1,
        hozAlign: "right",
        headerSort: false,
    }
];

// Export predate data as CSV
export const exportPredateCsv = async (
    filters?: Array<{field: string; type: string; value: string | number | boolean | null | undefined}>,
    startDate?: string,
    endDate?: string,
) : Promise<void> => {
    console.log('Exporting CSV...');

    try {
        const urlParams = new URLSearchParams();

        // Add date range parameters
        if (startDate) {
            urlParams.append('startDate', startDate);
        }
        if (endDate) {
            urlParams.append('endDate', endDate);
        }

        // Add filter parameters from Tabulator filters
        if (filters && Array.isArray(filters)) {
            filters.forEach((filter) => {
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
            });
        }

        const response = await axios.get('/shipments/predate/export' + '?' + urlParams.toString(), {
            responseType: 'blob',
        });

        const blob = response.data;
        // Extract filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let filename = `Predate_export_${new Date().toISOString().slice(0, 10)}.csv`;
        console.log('Content disposition: ', contentDisposition);
        if (contentDisposition) {
            const filenameMatch = contentDisposition.match(/filename[^;=\n]*=(['"]).*?\1|[^;\n]*/);
            if (filenameMatch && filenameMatch[1]) {
                filename = filenameMatch[1].replace(/['"]/g, '');
            }
        }
        // Create blob URL with proper filename
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.target = '_blank';
        link.click();
        window.URL.revokeObjectURL(url);
    } catch (error: unknown) {
        console.error('Error exporting predate CSV:', error);
        if (axios.isAxiosError(error) && error.response) {
            // When responseType is 'blob', errors come back as Blob, need to parse JSON
            if (error.response.data instanceof Blob) {
                const text = await error.response.data.text();
                const errorData = JSON.parse(text);
                throw new Error(errorData.message || 'Error exporting predate CSV');
            }
            throw new Error(error.response.statusText || 'Error exporting predate CSV');
        }
        throw error;
    }
};