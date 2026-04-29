import { useEffect, useState, useRef, useCallback, type ReactNode } from 'react';
import axios from '@/lib/axios';
import type { AwbData } from '@/types';
import type { ShipmentsFilters } from '@/types/shipments';
import { MobileRowCard } from '@/pages/shipments/mobile-row-card';

interface AwbMobileListProps {
    endpoint: string;
    filters: ShipmentsFilters;
    extraParams?: Record<string, string | number | boolean>;
    /** Slot for action buttons in expanded zone */
    rowActions?: (row: AwbData) => ReactNode;
    onSelectionChange?: (rows: AwbData[]) => void;
    /** Trigger refresh — bump key to refetch list */
    refreshKey?: number;
}

const PAGE_SIZE = 50;

export function AwbMobileList({
    endpoint,
    filters,
    extraParams = {},
    rowActions,
    onSelectionChange,
    refreshKey = 0,
}: AwbMobileListProps) {
    const [rows, setRows] = useState<AwbData[]>([]);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [loading, setLoading] = useState(false);
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const sentinelRef = useRef<HTMLDivElement>(null);
    const abortRef = useRef<AbortController | null>(null);

    const buildUrl = useCallback((p: number) => {
        const u = new URLSearchParams();
        Object.entries(extraParams).forEach(([k, v]) => u.append(k, String(v)));
        if (filters.dateStart) u.append('startDate', filters.dateStart);
        if (filters.dateEnd) u.append('endDate', filters.dateEnd);
        if (filters.q) u.append('q', filters.q);
        if (filters.judet) u.append('filter_judet', filters.judet);
        if (filters.tipObj) u.append('filter_tip_obj', String(filters.tipObj));
        if (filters.status) u.append('filter_status', filters.status);
        u.append('page', String(p));
        u.append('rows', String(PAGE_SIZE));
        return `${endpoint}?${u.toString()}`;
    }, [endpoint, filters, extraParams]);

    const loadPage = useCallback(async (p: number, replace: boolean) => {
        if (abortRef.current) abortRef.current.abort();
        const ctrl = new AbortController();
        abortRef.current = ctrl;
        setLoading(true);
        try {
            const res = await axios.get(buildUrl(p), { signal: ctrl.signal });
            const data: AwbData[] = res.data?.data?.data ?? [];
            const lastPage: number = res.data?.data?.last_page ?? 1;
            setRows((prev) => (replace ? data : [...prev, ...data]));
            setHasMore(p < lastPage);
            setPage(p);
        } catch (e) {
            if (axios.isCancel(e)) return;
            console.error('Mobile list load error', e);
        } finally {
            setLoading(false);
        }
    }, [buildUrl]);

    // Reload on filter / refreshKey change
    useEffect(() => {
        setSelectedIds(new Set());
        loadPage(1, true);
    }, [filters.q, filters.dateStart, filters.dateEnd, filters.status, filters.tipObj, filters.judet, refreshKey, loadPage]);

    // IntersectionObserver for infinite scroll
    useEffect(() => {
        const el = sentinelRef.current;
        if (!el || !hasMore || loading) return;
        const obs = new IntersectionObserver((entries) => {
            if (entries[0]?.isIntersecting) {
                loadPage(page + 1, false);
            }
        });
        obs.observe(el);
        return () => obs.disconnect();
    }, [hasMore, loading, page, loadPage]);

    // Notify parent of selection changes
    useEffect(() => {
        if (!onSelectionChange) return;
        const sel = rows.filter((r) => typeof r.id === 'number' && selectedIds.has(r.id));
        onSelectionChange(sel);
    }, [selectedIds, rows, onSelectionChange]);

    const handleSelect = useCallback((id: number) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id); else next.add(id);
            return next;
        });
    }, []);

    return (
        <div className="px-3 py-2">
            {rows.length === 0 && !loading && (
                <p className="text-center text-sm text-muted-foreground py-8">
                    Nu au fost găsite AWB-uri.
                </p>
            )}
            {rows.map((r) => (
                <MobileRowCard
                    key={r.id}
                    row={r}
                    selected={typeof r.id === 'number' && selectedIds.has(r.id)}
                    onSelect={handleSelect}
                    actions={rowActions}
                />
            ))}
            <div ref={sentinelRef} className="h-8" />
            {loading && (
                <p className="text-center text-xs text-muted-foreground py-2">
                    Se încarcă...
                </p>
            )}
        </div>
    );
}
