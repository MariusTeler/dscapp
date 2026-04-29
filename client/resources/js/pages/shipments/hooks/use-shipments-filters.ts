import { useState, useCallback, useEffect } from 'react';
import type { ShipmentsFilters, TipObj } from '@/types/shipments';

function ymd(d: Date): string {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

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

function readFromUrl(): Partial<ShipmentsFilters> {
    if (typeof window === 'undefined') return {};
    const p = new URLSearchParams(window.location.search);
    const out: Partial<ShipmentsFilters> = {};
    if (p.has('q')) out.q = p.get('q') ?? '';
    if (p.has('start')) out.dateStart = p.get('start') ?? '';
    if (p.has('end')) out.dateEnd = p.get('end') ?? '';
    if (p.has('status')) out.status = p.get('status') || null;
    if (p.has('tip')) {
        const v = Number(p.get('tip'));
        if (v === 1 || v === 2 || v === 3) out.tipObj = v as TipObj;
    }
    if (p.has('judet')) out.judet = p.get('judet') || null;
    return out;
}

function writeToUrl(filters: ShipmentsFilters, tab: string): void {
    if (typeof window === 'undefined') return;
    const p = new URLSearchParams(window.location.search);
    p.set('tab', tab);
    if (filters.q) p.set('q', filters.q); else p.delete('q');
    if (filters.dateStart) p.set('start', filters.dateStart); else p.delete('start');
    if (filters.dateEnd) p.set('end', filters.dateEnd); else p.delete('end');
    if (filters.status) p.set('status', filters.status); else p.delete('status');
    if (filters.tipObj) p.set('tip', String(filters.tipObj)); else p.delete('tip');
    if (filters.judet) p.set('judet', filters.judet); else p.delete('judet');
    const newUrl = `${window.location.pathname}?${p.toString()}`;
    window.history.replaceState({}, '', newUrl);
}

export function useShipmentsFilters(initialTab: string) {
    const [filters, setFiltersState] = useState<ShipmentsFilters>(() => ({
        ...defaultFilters(),
        ...readFromUrl(),
    }));

    // Write filters to URL whenever they change (or tab changes)
    useEffect(() => {
        writeToUrl(filters, initialTab);
    }, [filters, initialTab]);

    const setFilters = useCallback((next: ShipmentsFilters) => {
        setFiltersState(next);
    }, []);

    const resetFilters = useCallback(() => {
        setFiltersState(defaultFilters());
    }, []);

    return { filters, setFilters, resetFilters };
}
