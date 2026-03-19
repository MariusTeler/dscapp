import axios from '@/lib/axios';
import { autocomplete } from '@/routes/recipients';
import type { DestinatarAutocompleteOption } from '@/types';

// Destinatari response interface
export interface DestinatariResponse {
    success: boolean;
    message?: string;
    data: DestinatarAutocompleteOption[];
}

/**
 * Fetch destinatari for autocomplete
 */
export const fetchDestinatari = async (query?: string, limit?: number): Promise<DestinatariResponse> => {
    const params = new URLSearchParams();

    if (query) {
        params.append('q', query);
    }

    if (limit) {
        params.append('limit', limit.toString());
    }

    const url = autocomplete.url() + (params.toString() ? '?' + params.toString() : '');

    try {
        const response = await axios.get(url, {
            headers: {
                'Accept': 'application/json',
            },
        });
        return response.data;
    } catch (error) {
        if (axios.isAxiosError(error)) {
            throw new Error(`Error fetching destinatari: ${error.message}`);
        }
        throw new Error('Error fetching destinatari');
    }
};
