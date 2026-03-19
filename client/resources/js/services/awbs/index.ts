import axios from '@/lib/axios';
import { price, store, update, destroy, edit, pdf, masterPdf, puisoriPdf } from '@/routes/awb';
import type { AwbData, UpdatedAwbResponse } from '@/types';

const sanitizeAwbIds = (ids: Array<number | null | undefined>): number[] => {
    return ids.filter((id): id is number => typeof id === 'number' && Number.isInteger(id) && id > 0);
};

// Get by ID
export const getById = async (id: number): Promise<{ success: boolean; data?: AwbData; message?: string }> => {
    try {
        const response = await axios.get(edit.url(id));
        const result = response.data;

        if (result.success) {
            return { success: true, data: result.data };
        } else {
            return { success: false, message: result.message || 'Failed to fetch AWB' };
        }
    } catch (error: unknown) {
        console.error('Error fetching AWB:', error);
        if (error && typeof error === 'object' && 'response' in error) {
            const axiosError = error as { response?: { data?: { message?: string } } };
            if (axiosError.response?.data?.message) {
                return { success: false, message: axiosError.response.data.message };
            }
        }
        return { success: false, message: 'Error fetching AWB' };
    }
};

// Print AWBs PDF
export const printAwbsPdf = async (ids: Array<number>): Promise<void> => {
    const validIds = sanitizeAwbIds(ids);
    console.log('Printing AWB PDF...', validIds);

    if (validIds.length === 0) {
        throw new Error('No valid AWB IDs provided for printing.');
    }

    try {
        const response = await axios.get(pdf.url(), {
            params: { ids: validIds },
            responseType: 'blob',
        });

        const blob = response.data;
        // Extract filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let filename = `NTs.pdf`;
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
        console.error('Error printing AWB PDF:', error);
        throw error;
    }
};

// Print Masters AWB PDF
export const printMastersPdf = async (ids: Array<number>): Promise<void> => {
    const validIds = sanitizeAwbIds(ids);
    console.log('Printing Master AWB PDF...', validIds);

    if (validIds.length === 0) {
        throw new Error('No valid AWB IDs provided for master PDF printing.');
    }

    try {
        const response = await axios.get(masterPdf.url(), {
            params: { ids: validIds },
            responseType: 'blob',
        });

        const blob = response.data;
        // Extract filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let filename = `Master-NTs.pdf`;
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
        console.error('Error printing Master AWB PDF:', error);
        throw error;
    }
};

// Print Puisori AWB PDF
export const printPuisoriPdf = async (ids: Array<number>): Promise<void> => {
    const validIds = sanitizeAwbIds(ids);
    console.log('Printing Puisori AWB PDF...', validIds);

    if (validIds.length === 0) {
        throw new Error('No valid AWB IDs provided for puisori PDF printing.');
    }

    try {
        const response = await axios.get(puisoriPdf.url(), {
            params: { ids: validIds },
            responseType: 'blob',
        });

        const blob = response.data;
        // Extract filename from Content-Disposition header
        const contentDisposition = response.headers['content-disposition'];
        let filename = `Puisori-NTs.pdf`;
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
        console.error('Error printing Puisori AWB PDF:', error);
        throw error;
    }
};

// Create AWB
export const createAwb = async (data: AwbData): Promise<UpdatedAwbResponse> => {
    try {
        const response = await axios.post(store.url(), data, {
            headers: {
                'Content-Type': 'application/json',
            },
        });
        const result = response.data;

        if (result.success) {
            return result;
        } else {
            return { success: false, message: result.message || 'Failed to create AWB' };
        }
    } catch (error: unknown) {
        console.error('Error creating AWB:', error);
        if (error && typeof error === 'object' && 'response' in error) {
            const axiosError = error as { response?: { data?: { message?: string } } };
            if (axiosError.response?.data?.message) {
                return { success: false, message: axiosError.response.data.message };
            }
        }
        return { success: false, message: 'Error creating AWB' };
    }
}

// Update AWB
export const updateAwb = async (id: number | undefined, data: AwbData): Promise<UpdatedAwbResponse> => {
    if(!id) {
        return { success: false, message: 'Invalid AWB ID' };
    }

    try {
        const response = await axios.put(update.url(id), data);
        const result = response.data;

        if (result.success) {
            return result;
        } else {
            return { success: false, message: result.message || 'Failed to update AWB' };
        }
    } catch (error: unknown) {
        if (error && typeof error === 'object' && 'response' in error) {
            const axiosError = error as { response?: { data?: { message?: string } } };
            console.error('Error updating AWB:', axiosError);
            if (axiosError.response?.data?.message) {
                return { success: false, message: axiosError.response.data.message };
            }
        }
        return { success: false, message: 'Error updating AWB' };
    }
};

// Delete AWB
export const deleteAwb = async (id: number | string): Promise<{ success: boolean; message?: string }> => {
    try {
        const response = await axios.delete(destroy.url(id));
        const result = response.data;

        if (result.success) {
            return { success: true, message: result.message };
        } else {
            return { success: false, message: result.message || 'Failed to delete AWB' };
        }
    } catch (error: unknown) {
        console.error('Error deleting AWB:', error);
        if (error && typeof error === 'object' && 'response' in error) {
            const axiosError = error as { response?: { data?: { message?: string } } };
            if (axiosError.response?.data?.message) {
                return { success: false, message: axiosError.response.data.message };
            }
        }
        return { success: false, message: 'Error deleting AWB' };
    }
};

// Estimate Cost
export const estimateCost = async (data: AwbData): Promise<{ success: boolean; message?: string }> => {
    try {
        const response = await axios.post(price.url(), data, {
            headers: {
                'Content-Type': 'application/json',
            },
        });
        const result = response.data;

        if (result.success) {
            return result;
        } else {
            return { success: false, message: result.message || 'Failed to estimate price' };
        }
    } catch (error: unknown) {
        console.error('Error estimating price:', error);
        if (error && typeof error === 'object' && 'response' in error) {
            const axiosError = error as { response?: { data?: { message?: string } } };
            if (axiosError.response?.data?.message) {
                return { success: false, message: axiosError.response.data.message };
            }
        }
        return { success: false, message: 'Error estimating price' };
    }
}
