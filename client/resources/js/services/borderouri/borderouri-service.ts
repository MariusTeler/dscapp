import axios from '@/lib/axios';
import { data, pdf, awbsPdf, puisoriPdf, masterPdf, exportMethod as exportCsv } from '@/routes/slips';
import type { ColumnDefinition, ColumnComponent } from 'tabulator-tables';
import { formatDate } from '@/lib/formatters';
import type { BorderouData, TabulatorDataResponse as DataResponse} from '@/types';

// Load data with pagination
export const loadData = async (params: {
    page?: number;
    rows?: number;
    sort?: Array<{column?: ColumnComponent; field: string; dir: 'asc' | 'desc'}>;
    filters?: Array<{field: string; type: string; value: string | number | boolean | null | undefined}>;
}): Promise<DataResponse<BorderouData> | null> => {
    console.log('Loading data with pagination...', params);

    try {
        const urlParams = new URLSearchParams();

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
                if (typeof value === 'string' && value.trim()) {
                    urlParams.append(`filter_${field}`, value.trim());
                } else if (typeof value === 'number') {
                    urlParams.append(`filter_${field}`, value.toString());
                }
            });
        }

        const response = await axios.get(data.url() + '?' + urlParams.toString());
        const result = response.data;

        if (result.success && result.data && 'data' in result.data) {
            console.log('Data loaded:', result.data.data?.length || 0, 'records, page:', result.data.current_page || 'N/A');
            return result;
        } else {
            console.error('Failed to load data');
            throw new Error('Failed to load data: ' + result.message);
        }
    } catch (error) {
        console.error('Error loading data:', error);
        throw new Error('Failed to load data: Internal server error');
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
        width: 60,
        headerSort: false,
    },
    {
        title: "ID",
        field: "borderou_id",
        widthGrow: 1,
        headerHozAlign: "center",
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        formatter: (cell) => `<strong>#${cell.getValue()}</strong>`,
        hozAlign: "center"
    },
    {
        title: "Awb-uri",
        field: "expeditii",
        headerHozAlign: "center",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        hozAlign: "center"
    },
    {
        title: "Data",
        field: "created_at",
        headerHozAlign: "center",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        hozAlign: "center",
        formatter: (cell) => {
            const date = cell.getValue();
            return formatDate(date);
        }
    }
];

// Action handlers interface
export interface RowDataActions {
    onPrintBorderou: (rowData: BorderouData) => void;
    onPrintBoAwbs: (rowData: BorderouData) => void;
    onExportCsv: (rowData: BorderouData) => void;
}

// Actions column definition for table
export const actionsColumns = (actions: RowDataActions): ColumnDefinition => ({
    title: "Actiuni",
    field: "actions",
    width: 140,
    headerSort: false,
    resizable: false,
    formatter: () => {
        return `
            <div class="flex gap-1 justify-center">
                <button class="action-btn print-borderou-btn px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600" title="Borderou in PDF">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 9V2h12v7"/>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                        <path d="M6 14h12v8H6z"/>
                    </svg>
                </button>
                <button class="action-btn print-awbs-btn px-2 py-1 text-xs bg-green-500 text-white rounded hover:bg-green-600" title="Awb-uri in PDF">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <path d="M14 2v6h6"/>
                        <path d="M16 13H8"/>
                        <path d="M16 17H8"/>
                        <path d="M10 9H8"/>
                    </svg>
                </button>
                <button class="action-btn export-csv-btn px-2 py-1 text-xs bg-orange-500 text-white rounded hover:bg-orange-600" title="Exporta CSV">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <path d="M7 10l5 5 5-5"/>
                        <path d="M12 15V3"/>
                    </svg>
                </button>
            </div>
        `;
    },
    cellClick: (e, cell) => {
        const target = e.target as HTMLElement;
        const button = target.closest('.action-btn');
        if (!button) return;

        const rowData = cell.getRow().getData() as BorderouData;

        if (button.classList.contains('print-borderou-btn')) {
            actions.onPrintBorderou(rowData);
        } else if (button.classList.contains('print-awbs-btn')) {
            actions.onPrintBoAwbs(rowData);
        } else if (button.classList.contains('export-csv-btn')) {
            actions.onExportCsv(rowData);
        }
    }
});

// API response interface
export interface ApiResponse<T = unknown> {
    success: boolean;
    message: string;
    data?: T;
}

// Helper function to get CSRF token
// Create new borderou with selected AWB IDs
export const createBorderou = async (awbIds: number[]): Promise<ApiResponse<BorderouData>> => {
    console.log('Creating borderou with AWB IDs:', awbIds);

    try {
        const response = await axios.post('/slip', { awb_ids: awbIds }, {
            headers: {
                'Content-Type': 'application/json',
            },
        });
        const result = response.data;
        console.log('Borderou created:', result);
        return result;
    } catch (error) {
        if (error && typeof error === 'object' && 'response' in error) {
            const axiosError = error as { response?: { data?: { message?: string } } };
            if (axiosError.response?.data?.message) {
                return { success: false, message: axiosError.response.data.message };
            }
        }
        console.error('Error creating borderou:', error);
        return { success: false, message: 'Error creating borderou' };
    }
};

// Delete borderou
export const deleteBorderou = async (id: number): Promise<ApiResponse> => {
    console.log('Deleting borderou...', id);

    try {
        const response = await axios.delete(`/slip/${id}`);
        const result = response.data;
        console.log('Borderou deleted:', result);
        return result;
    } catch (error) {
        if (error && typeof error === 'object' && 'response' in error) {
            const axiosError = error as { response?: { data?: { message?: string } } };
            if (axiosError.response?.data?.message) {
                return { success: false, message: axiosError.response.data.message };
            }
        }
        console.error('Error deleting borderou:', error);
        return { success: false, message: 'Error deleting borderou' };
    }
};

// Print borderou as PDF
export const printBorderouPdf = async (id: number): Promise<void> => {
    console.log('Printing borderou PDF...', id);

    try {
        const pdfUrl = pdf.url(id);
        const response = await axios.get(pdfUrl, {
            responseType: 'blob',
        });

        const blob = response.data;
        // Extract filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let filename = `Borderou-${id}.pdf`;
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
        if (axios.isAxiosError(error) && error.response) {
            // When responseType is 'blob', errors come back as Blob, need to parse JSON
            if (error.response.data instanceof Blob) {
                const text = await error.response.data.text();
                const errorData = JSON.parse(text);
                throw new Error(errorData.message || 'Error printing borderou PDF');
            }
            throw new Error(error.response.statusText || 'Error printing borderou PDF');
        }
        throw error;
    }
};

// Print AWBs from borderou as PDF
export const printBoAwbsPdf = async (id: number): Promise<void> => {
    console.log('Printing AWBs PDF...', id);

    try {
        const pdfUrl = awbsPdf.url(id);
        const response = await axios.get(pdfUrl, {
            responseType: 'blob',
        });

        const blob = response.data;
        // Extract filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let filename = `NT-in-Borderou-${id}.pdf`;
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
        if (axios.isAxiosError(error) && error.response) {
            // When responseType is 'blob', errors come back as Blob, need to parse JSON
            if (error.response.data instanceof Blob) {
                const text = await error.response.data.text();
                const errorData = JSON.parse(text);
                throw new Error(errorData.message || 'Error printing AWBs PDF');
            }
            throw new Error(error.response.statusText || 'Error printing AWBs PDF');
        }
        throw error;
    }
};

// Print Master AWBs PDF
export const printBoMasterPdf = async (id: number): Promise<void> => {
    console.log('Printing Master AWBs PDF...', id);

    try {
        const pdfUrl = masterPdf.url(id);
        const response = await axios.get(pdfUrl, {
            responseType: 'blob',
        });

        const blob = response.data;
        // Extract filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let filename = `Master-NT-in-Borderou-${id}.pdf`;
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
        console.error('Error printing Master AWBs PDF:', error);
        if (axios.isAxiosError(error) && error.response) {
            // When responseType is 'blob', errors come back as Blob, need to parse JSON
            if (error.response.data instanceof Blob) {
                const text = await error.response.data.text();
                const errorData = JSON.parse(text);
                throw new Error(errorData.message || 'Error printing Master AWBs PDF');
            }
            throw new Error(error.response.statusText || 'Error printing Master AWBs PDF');
        }
        throw error;
    }
};

// Print Puisori AWBs PDF
export const printBoPuisoriPdf = async (id: number): Promise<void> => {
    console.log('Printing Puisori AWBs PDF...', id);

    try {
        const pdfUrl = puisoriPdf.url(id);
        const response = await axios.get(pdfUrl, {
            responseType: 'blob',
        });

        const blob = response.data;
        // Extract filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let filename = `Puisori-NT-in-Borderou-${id}.pdf`;
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
        console.error('Error printing Puisori AWBs PDF:', error);
        if (axios.isAxiosError(error) && error.response) {
            if (error.response.data instanceof Blob) {
                const text = await error.response.data.text();
                const errorData = JSON.parse(text);
                throw new Error(errorData.message || 'Error printing Puisori AWBs PDF');
            }
            throw new Error(error.response.statusText || 'Error printing Puisori AWBs PDF');
        }
        throw error;
    }
};

// Export borderou data as CSV
export const exportBorderouCsv = async (id: number): Promise<void> => {
    console.log('Exporting borderou CSV...', id);

    try {
        const csvUrl = exportCsv.url(id);
        const response = await axios.get(csvUrl, {
            responseType: 'blob',
        });

        const blob = response.data;
        // Extract filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let filename = `Borderou-${id}.csv`;
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
        console.error('Error exporting borderou CSV:', error);
        if (axios.isAxiosError(error) && error.response) {
            if (error.response.data instanceof Blob) {
                const text = await error.response.data.text();
                const errorData = JSON.parse(text);
                throw new Error(errorData.message || 'Error exporting borderou CSV');
            }
            throw new Error(error.response.statusText || 'Error exporting borderou CSV');
        }
        throw error;
    }
};
