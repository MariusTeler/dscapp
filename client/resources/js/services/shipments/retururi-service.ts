import { retururi } from '@/routes/shipments';
import type { ColumnDefinition, ColumnComponent } from 'tabulator-tables';
import { formatWeight, formatDate, formatTipObject, formatTipAwb } from '@/lib/formatters';
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
                if (field === 'tip_exp' && typeof value === 'number') {
                    urlParams.append(`filter_${field}`, value.toString());
                } else if (typeof value === 'string' && value.trim()) {
                    urlParams.append(`filter_${field}`, value.trim());
                } else if (typeof value === 'number') {
                    urlParams.append(`filter_${field}`, value.toString());
                }
            });
        }

        const response = await axios.get(retururi().url + '?' + urlParams.toString(), {
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
        title: "Tip ret.",
        field: "tip_exp",
        widthGrow: 1,
        frozen: true,
        hozAlign: "center",
        headerFilter: "list",
        headerFilterParams: {
            values: {
                "": "...",
                "1": "Retur NT",
                "2": "Retur doc.",
                "6": "Retur Amb.",
                "7": "Retur Colet",
                "5": "Returnare"
            }
        },
        formatter: (cell) => {
            const value = cell.getValue();
            const { label, colorClass } = formatTipAwb(value);
            return `<span class="px-2 py-1 text-xs rounded-full ${colorClass}">${label}</span>`;
        }
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
        title: "Localitate",
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
        title: "Localitate",
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
        title: "Data Col.",
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
        title: "Data status",
        field: "data_status",
        headerHozAlign: "center",
        widthGrow: 1,
        formatter: (cell) => formatDate(cell.getValue()),
        hozAlign: "center"
    },
    {
        title: "Checkpoint",
        field: "checkpoint",
        headerHozAlign: "center",
        widthGrow: 1,
        hozAlign: "center",
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Centru",
        field: "centru",
        headerHozAlign: "center",
        widthGrow: 1,
        hozAlign: "center",
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    }
];

// Export retururi data as CSV
export const exportRetururiCsv = async (
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

        const response = await axios.get('/shipments/retururi/export' + '?' + urlParams.toString(), {
            responseType: 'blob',
        });

        const blob = response.data;
        // Extract filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let filename = `Retururi_export_${new Date().toISOString().slice(0, 10)}.csv`;
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
    } catch (error) {
        console.error('Error exporting retururi CSV:', error);
        if (axios.isAxiosError(error)) {
            const errorData = error.response?.data || {};
            throw new Error('Error exporting retururi CSV: ' + errorData.message || `Eroare HTTP: ${error.response?.status}`);
        }
        throw error;
    }
};