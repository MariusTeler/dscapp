import axios from '@/lib/axios';
import { autocomplete as localitatiList } from '@/routes/commons/localitati';
import type { LocalitateAutocompleteOption } from '@/types';

// Localitati response interface
export interface LocalitatiResponse {
    success: boolean;
    message?: string;
    data: LocalitateAutocompleteOption[];
}

/**
 * Fetch localitati for autocomplete
 */
export const fetchLocalitati = async (query?: string, limit?: number): Promise<LocalitatiResponse> => {
    const params = new URLSearchParams();

    if (query) {
        params.append('q', query);
    }

    if (limit) {
        params.append('limit', limit.toString());
    }

    const url = localitatiList.url() + (params.toString() ? '?' + params.toString() : '');

    try {
        const response = await axios.get(url, {
            headers: {
                'Accept': 'application/json',
            },
        });
        return response.data;
    } catch (error) {
        if (axios.isAxiosError(error)) {
            throw new Error('Failed to fetch localitati');
        }
        throw new Error('Failed to fetch localitati');
    }
};
