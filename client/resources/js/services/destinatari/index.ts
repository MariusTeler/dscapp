import axios from '@/lib/axios';
import { data, edit, store, update, destroy } from '@/routes/recipients';
import type { ColumnDefinition, ColumnComponent } from 'tabulator-tables';
import { DestinatarData, TabulatorDataResponse as DataResponse } from '@/types';

// Load data with pagination
export const loadData = async (params: {
    page?: number;
    rows?: number;
    sort?: Array<{column?: ColumnComponent; field: string; dir: 'asc' | 'desc'}>;
    filters?: Array<{field: string; type: string; value: string | number | boolean | null | undefined}>;
}): Promise<DataResponse<DestinatarData> | null> => {
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
        hozAlign: "center",
        width: 60,
        headerSort: false
    },
    {
        title: "Nume",
        field: "nume",
        widthGrow: 2,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        formatter: (cell) => `<strong>${cell.getValue()}</strong>`
    },
    {
        title: "Judet",
        field: "judet",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Localitate",
        field: "localitate",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Adresa",
        field: "adresa",
        widthGrow: 2,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Contact",
        field: "contact",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Telefon",
        field: "telefon",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        formatter: (cell) => {
            const phone = cell.getValue();
            if (phone) {
                return `<a href="tel:${phone}" class="text-blue-600 hover:text-blue-800">${phone}</a>`;
            }
            return '';
        }
    },
    {
        title: "Email",
        field: "email",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        formatter: (cell) => {
            const email = cell.getValue();
            if (email) {
                return `<a href="mailto:${email}" class="text-blue-600 hover:text-blue-800">${email}</a>`;
            }
            return '';
        }
    }
];

// Action handlers interface
export interface RowDataActions {
    onEdit: (rowData: DestinatarData) => void;
    onDelete: (rowData: DestinatarData) => void;
}

// Actions column definition for table
export const actionsColumns = (actions: RowDataActions): ColumnDefinition => ({
    title: "Actiuni",
    field: "actions",
    width: 100,
    headerSort: false,
    resizable: false,
    formatter: () => {
        return `
            <div class="flex gap-1 justify-center">
                <button class="action-btn edit-btn px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600" title="Editare">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </button>
                <button class="action-btn delete-btn px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600" title="Stergere">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                        <path d="M10 11v6M14 11v6"/>
                    </svg>
                </button>
            </div>
        `;
    },
    cellClick: (e, cell) => {
        const target = e.target as HTMLElement;
        const button = target.closest('.action-btn');
        if (!button) return;

        const rowData = cell.getRow().getData() as DestinatarData;

        if (button.classList.contains('edit-btn')) {
            actions.onEdit(rowData);
        } else if (button.classList.contains('delete-btn')) {
            actions.onDelete(rowData);
        }
    }
});

// API response interface
export interface ApiResponse<T = unknown> {
    success: boolean;
    message: string;
    data?: T;
}

//Get destinatar by id
export const editDestinatar = async (id: number): Promise<ApiResponse<DestinatarData>> => {
    console.log('Getting destinatar by id...', id);

    try {
        const response = await axios.get(edit.url(id));
        const result = response.data;
        console.log('Destinatar found:', result);
        return result;
    } catch (error) {
        console.error('Error getting destinatar:', error);
        return { success: false, message: 'Error getting destinatar: ' + error };
    }
};

// Create new destinatar
export const createDestinatar = async (formData: DestinatarData): Promise<ApiResponse> => {
    console.log('Creating destinatar...', formData);

    try {
        const response = await axios.post(store.url(), formData, {
            headers: {
                'Content-Type': 'application/json',
            },
        });
        const result = response.data;
        console.log('Destinatar created:', result);
        return result;
    } catch (error) {
        console.error('Error creating destinatar:', error);
        return { success: false, message: 'Error creating destinatar: ' + error };
    }
};

// Update existing destinatar
export const updateDestinatar = async (id: number, formData: DestinatarData): Promise<ApiResponse> => {
    console.log('Updating destinatar...', id, formData);
    try {
        const response = await axios.put(update.url(id), formData, {
            headers: {
                'Content-Type': 'application/json',
            },
        });
        const result = response.data;
        console.log('Destinatar updated:', result);
        return result;
    } catch (error) {
        console.error('Error updating destinatar:', error);
        return { success: false, message: 'Error updating destinatar: '+error };
    }
};
// Delete destinatar
export const deleteDestinatar = async (id: number): Promise<ApiResponse> => {
    console.log('Deleting destinatar...', id);

    try {
        const response = await axios.delete(destroy.url(id));
        const result = response.data;
        console.log('Destinatar deleted:', result);
        return result;
    } catch (error) {
        console.error('Error deleting destinatar:', error);
        return { success: false, message: 'Error deleting destinatar: ' + error };
    }
};