import { useEffect, useState, useRef } from 'react';
import axios from '@/lib/axios';
import type { ShipmentsTab, ShipmentsFilters, ShipmentsStats, ShipmentsStatsResponse } from '@/types/shipments';

interface UseShipmentsStatsOptions {
    enabled?: boolean; // dacă false, hook-ul nu fetch-uiește
    debounceMs?: number;
}

export function useShipmentsStats(
    tab: ShipmentsTab,
    filters: ShipmentsFilters,
    options: UseShipmentsStatsOptions = {}
) {
    const { enabled = true, debounceMs = 400 } = options;
    const [stats, setStats] = useState<ShipmentsStats | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const debounceTimer = useRef<number | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        if (!enabled) return;

        if (debounceTimer.current) window.clearTimeout(debounceTimer.current);
        debounceTimer.current = window.setTimeout(async () => {
            // Cancel previous in-flight
            if (abortRef.current) abortRef.current.abort();
            const ctrl = new AbortController();
            abortRef.current = ctrl;

            const params = new URLSearchParams();
            if (filters.q) params.append('q', filters.q);
            if (filters.dateStart) params.append('startDate', filters.dateStart);
            if (filters.dateEnd) params.append('endDate', filters.dateEnd);
            if (filters.judet) params.append('filter_judet', filters.judet);
            if (filters.tipObj) params.append('filter_tip_obj', String(filters.tipObj));
            if (filters.status) params.append('filter_status', filters.status);

            setLoading(true);
            setError(null);
            try {
                const res = await axios.get<ShipmentsStatsResponse>(
                    `/shipments/${tab}/stats?${params.toString()}`,
                    { signal: ctrl.signal }
                );
                if (res.data.success && res.data.data) {
                    setStats(res.data.data);
                } else {
                    setError(res.data.message ?? 'Stats fetch failed');
                }
            } catch (e) {
                if (axios.isCancel(e)) return;
                setError(e instanceof Error ? e.message : 'Stats fetch error');
            } finally {
                setLoading(false);
            }
        }, debounceMs);

        return () => {
            if (debounceTimer.current) window.clearTimeout(debounceTimer.current);
        };
    }, [tab, filters, enabled, debounceMs]);

    return { stats, loading, error };
}
