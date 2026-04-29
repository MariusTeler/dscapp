import { useState, useCallback } from 'react';
import type { ShipmentsFilters } from '@/types/shipments';

function defaultFilters(): ShipmentsFilters {
    const today = new Date();
    const monthAgo = new Date();
    monthAgo.setMonth(monthAgo.getMonth() - 1);
    return {
        q: '',
        dateStart: ymd(monthAgo),
        dateEnd: ymd(today),
        status: null,
        tipObj: null,
        judet: null,
    };
}

function ymd(d: Date): string {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

export function useShipmentsFilters() {
    const [filters, setFilters] = useState<ShipmentsFilters>(defaultFilters);

    const updateFilters = useCallback((next: ShipmentsFilters) => {
        setFilters(next);
    }, []);

    const resetFilters = useCallback(() => {
        setFilters(defaultFilters());
    }, []);

    return { filters, setFilters: updateFilters, resetFilters };
}
