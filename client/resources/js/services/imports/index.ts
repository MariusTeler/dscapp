import axios from '@/lib/axios';
import { validate as validateAwbsImp, run as runAwbsImp } from '@/routes/awb/import';
import { validate as validateRecipientsImp, run as runRecipientsImp } from '@/routes/recipients/import';
import { type ImportValidationResponse } from '@/types';

/**
 * Validate CSV file for AWB import
 */
export async function validateAwbImport(file: File): Promise<ImportValidationResponse> {
    const formData = new FormData();
    formData.append('awbsFile', file);

    try {
        const response = await axios.post(validateAwbsImp.url(), formData, {
            headers: {
                'Accept': 'application/json',
            },
        });
        return response.data;
    } catch (error) {
        if (axios.isAxiosError(error)) {
            const errorData = error.response?.data || {};
            return {
                success: false,
                message: errorData.message || `Eroare HTTP: ${error.response?.status}`,
            };
        }
        console.error('Error validating AWB import:', error);
        return {
            success: false,
            message: error instanceof Error ? error.message : 'Eroare la validarea fisierului',
        };
    }
}

/**
 * Validate CSV file for Recipients (Destinatari) import
 */
export async function validateRecipientsImport(file: File): Promise<ImportValidationResponse> {
    const formData = new FormData();
    formData.append('destFile', file);

    try {
        const response = await axios.post(validateRecipientsImp.url(), formData, {
            headers: {
                'Accept': 'application/json',
            },
        });
        return response.data;
    } catch (error) {
        if (axios.isAxiosError(error)) {
            const errorData = error.response?.data || {};
            return {
                success: false,
                message: errorData.message || `Eroare HTTP: ${error.response?.status}`,
            };
        }
        console.error('Error validating recipients import:', error);
        return {
            success: false,
            message: error instanceof Error ? error.message : 'Eroare la validarea fisierului',
        };
    }
}

/**
 * Import validated AWB data
 */
export async function importAwbData(importId: string): Promise<ImportValidationResponse> {
    const formData = new FormData();
    formData.append('import_id', importId);

    try {
        const response = await axios.post(runAwbsImp.url(), formData, {
            headers: {
                'Accept': 'application/json',
            },
        });
        return response.data;
    } catch (error) {
        if (axios.isAxiosError(error)) {
            const errorData = error.response?.data || {};
            return {
                success: false,
                message: errorData.message || `Eroare HTTP: ${error.response?.status}`,
            };
        }
        console.error('Error importing AWB data:', error);
        return {
            success: false,
            message: error instanceof Error ? error.message : 'Eroare la importul datelor',
        };
    }
}

/**
 * Import validated Recipients data
 */
export async function importRecipientsData(importId: string): Promise<ImportValidationResponse> {
    const formData = new FormData();
    formData.append('import_id', importId);
    try {
        const response = await axios.post(runRecipientsImp.url(), formData, {
            headers: {
                'Accept': 'application/json',
            },
        });
        return response.data;
    } catch (error) {
        if (axios.isAxiosError(error)) {
            const errorData = error.response?.data || {};
            return {
                success: false,
                message: errorData.message || `Eroare HTTP: ${error.response?.status}`,
            };
        }
        console.error('Error importing recipients data:', error);
        return {
            success: false,
            message: error instanceof Error ? error.message : 'Eroare la importul datelor',
        };
    }
}
