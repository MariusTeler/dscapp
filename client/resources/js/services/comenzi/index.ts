import axios from '@/lib/axios';
import { data, edit, store, update, destroy } from '@/routes/orders';
import type { ColumnDefinition, ColumnComponent } from 'tabulator-tables';
import { formatDate, formatWeight, formatStatusComandaHtml } from '@/lib/formatters';
import type { OrderData, TabulatorDataResponse as DataResponse } from '@/types';

// Create order response interface
export interface CreateOrderResponse {
    success: boolean;
    message?: string;
    data?: {
        id: number;
        [key: string]: unknown;
    };
    errors?: {
        [key: string]: string[];
    };
}

// Load data with pagination
export const loadData = async (params: {
    page?: number;
    rows?: number;
    sort?: Array<{column?: ColumnComponent; field: string; dir: 'asc' | 'desc'}>;
    filters?: Array<{field: string; type: string; value: string | number | boolean | null | undefined}>;
}): Promise<DataResponse<OrderData> | null> => {
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

                // Special handling for status field - ensure numeric value
                if (field === 'status') {
                    const statusValue = typeof value === 'string' ? value.trim() : value.toString();
                    if (statusValue && statusValue !== '') {
                        urlParams.append('filter_status', statusValue);
                    }
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
        title: "Nr. Comanda",
        field: "id",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        formatter: (cell) => `<strong>#${cell.getValue()}</strong>`,
        hozAlign: "center"
    },
    {
        title: "Data colectare",
        field: "collected_at",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        formatter: (cell) => {
            const date = cell.getValue();
            return formatDate(date);
        },
        hozAlign: "center",
    },
    {
        title: "Data status",
        field: "data_status",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        formatter: (cell) => {
            const date = cell.getValue();
            return formatDate(date);
        },
        hozAlign: "center",
    },
    {
        title: "Status",
        field: "status",
        widthGrow: 1,
        headerHozAlign: "center",
        headerFilter: "list",
        headerFilterParams: {
            values: {
                "": "...",
                "1": "Initiala",
                "2": "Transmisa",
                "3": "Distribuita",
                "4": "Acceptata",
                "5": "Refuzata",
                "6": "Colectata",
                "7": "Anulata"
            }
        },
        hozAlign: "center",
        formatter: (cell) => formatStatusComandaHtml(cell.getValue())
    },
    {
        title: "Motiv",
        field: "motiv",
        widthGrow: 2,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
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
        title: "Adresa",
        field: "expeditor_adresa",
        widthGrow: 2,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Colete",
        field: "colete",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        hozAlign: "center"
    },
    {
        title: "Paleti",
        field: "paleti",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        hozAlign: "center"
    },
    {
        title: "Greutate (kg)",
        field: "greutate",
        widthGrow: 1,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        hozAlign: "right",
        formatter: (cell) => formatWeight(cell.getValue())
    },
    {
        title: "Observatii",
        field: "observatii",
        widthGrow: 2,
        headerFilter: "input",
        headerFilterPlaceholder: "..."
    },
    {
        title: "Curier",
        field: "curier",
        widthGrow: 2,
        headerFilter: "input",
        headerFilterPlaceholder: "...",
        formatter: (cell) => `<strong>${cell.getValue() ?? ''}</strong>`
    }
];

// Action handlers interface
export interface RowDataActions {
    onEdit: (rowData: OrderData) => void;
    onDelete: (rowData: OrderData) => void;
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

        const rowData = cell.getRow().getData() as OrderData;

        if (button.classList.contains('edit-btn')) {
            actions.onEdit(rowData);
        } else if (button.classList.contains('delete-btn')) {
            actions.onDelete(rowData);
        }
    }
});

// Get by ID
export const editOrder = async (id: number): Promise<{ success: boolean; data?: OrderData; message?: string }> => {
    try {
        const response = await axios.get(edit.url(id));
        const result = response.data;
        
        if (result.success) {
            return { success: true, data: result.data };
        } else {
            return { success: false, message: result.message || 'Failed to fetch Comanda' };
        }
    } catch (error) {
        if (error && typeof error === 'object' && 'response' in error) {
            const axiosError = error as { response?: { data?: { message?: string } } };
            if (axiosError.response?.data?.message) {
                return { success: false, message: axiosError.response.data.message };
            }
        }
        return { success: false, message: 'Error fetching Comanda' };
    }
};

// Create new order
export const createOrder = async (orderData: OrderData): Promise<CreateOrderResponse> => {
    console.log('Creating order...', orderData);

    try {
        const response = await axios.post(store.url(), orderData, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        });
        const result = response.data;

        console.log('✅ Order created successfully:', result);
        return {
            success: true,
            message: result.message || 'Comanda a fost creata cu succes!',
            data: result.data,
        };
    } catch (error) {
        if (axios.isAxiosError(error)) {
            const result = error.response?.data;
            console.error('❌ Order creation failed:', result);
            return {
                success: false,
                message: result?.message || 'Eroare la crearea comenzii',
                errors: result?.errors,
            };
        }
        console.error('❌ Error creating order:', error);
        return {
            success: false,
            message: 'Eroare de comunicare cu serverul',
        };
    }
};

//update new order
export const updateOrder = async (id: number, orderData: OrderData): Promise<CreateOrderResponse> => {
    console.log('Updating order...', id, orderData);

    try {
        const response = await axios.put(update.url(id), orderData, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        });
        const result = response.data;

        console.log('✅ Order updated successfully:', result);
        return {
            success: true,
            message: result.message || 'Comanda a fost actualizata cu succes!',
            data: result.data,
        };
    } catch (error) {
        if (axios.isAxiosError(error)) {
            const result = error.response?.data;
            console.error('❌ Order update failed:', result);
            return {
                success: false,
                message: result?.message || 'Eroare la actualizarea comenzii',
                errors: result?.errors,
            };
        }
        console.error('❌ Error updating order:', error);
        return {
            success: false,
            message: 'Eroare de comunicare cu serverul',
        };
    }
}

//Delete order
export const deleteOrder = async (id: number): Promise<boolean> => {
    console.log('Deleting order...', id);

    try {
        await axios.delete(destroy.url(id), {
            headers: {
                'Accept': 'application/json',
            },
        });

        console.log('✅ Order deleted successfully');
        return true;
    } catch (error) {
        if (axios.isAxiosError(error)) {
            const result = error.response?.data;
            console.error('❌ Order deletion failed:', result);
            return result && result.message ? false : false;
        }
        console.error('❌ Error deleting order:', error);
        return false;
    }
}
