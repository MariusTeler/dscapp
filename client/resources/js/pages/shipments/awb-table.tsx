import { useEffect, useMemo, useRef } from 'react';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import type { ColumnDefinition } from 'tabulator-tables';
import type { AwbData, UserPrefs } from '@/types';
import type { ShipmentsFilters } from '@/types/shipments';
import { buildAnchoredRegex } from '@/lib/utils';

interface AwbTableProps {
    /** Endpoint URL pentru ajax (ex: shipments.nepredate URL) */
    endpoint: string;
    columns: ColumnDefinition[];
    filters: ShipmentsFilters;
    /** Param suplimentar (ex: swapped pentru nepredate) */
    extraParams?: Record<string, string | number | boolean>;
    prefs?: UserPrefs;
    /** Trigger pentru refresh granular: create/update/delete pe rând */
    createUpdateDeleteRow?: { rowId: number | undefined; newAwbData: AwbData | null; action: 'create' | 'update' | 'delete' | 'bo' | null };
    /** Trigger pentru actualizare „printed_at" pe rânduri */
    printRows?: { rowIds: number[]; printed_by_user: string | null } | null;
    onSelectionChange?: (rows: AwbData[]) => void;
    onRowDblClick?: (row: AwbData) => void;
    /** Callback la dataLoaded — pentru a actualiza counter-e externe */
    onDataLoaded?: (info: { displayed: number; total: number }) => void;
}

const createEmptyResponse = (rows = 100) => ({
    success: true,
    data: { data: [], total: 0, current_page: 1, per_page: rows, last_page: 1, from: null, to: null },
});

export function AwbTable({
    endpoint,
    columns,
    filters,
    extraParams = {},
    prefs,
    createUpdateDeleteRow,
    printRows,
    onSelectionChange,
    onRowDblClick,
    onDataLoaded,
}: AwbTableProps) {
    const tableRef = useRef<HTMLDivElement>(null);
    const tabulatorRef = useRef<Tabulator | null>(null);
    const filtersRef = useRef(filters);
    const extraParamsRef = useRef(extraParams);

    const minCharsFilter = prefs?.min_chars_filter ?? 3;
    const awbRegexp = prefs?.awb_regexp ?? '.*';

    // Keep refs in sync (Tabulator ajaxRequestFunc reads them)
    useEffect(() => {
        filtersRef.current = filters;
        extraParamsRef.current = extraParams;
    }, [filters, extraParams]);

    // Re-fetch when filters change (after init)
    useEffect(() => {
        if (tabulatorRef.current) {
            tabulatorRef.current.setData();
        }
    }, [filters.q, filters.dateStart, filters.dateEnd, filters.status, filters.tipObj, filters.judet]);

    // Memoize columns to prevent unnecessary re-init
    const memoColumns = useMemo(() => columns, [columns]);

    // Init Tabulator
    useEffect(() => {
        if (tableRef.current && !tabulatorRef.current) {
            let isMounted = true;

            tabulatorRef.current = new Tabulator(tableRef.current, {
                height: 720,
                progressiveLoad: 'scroll',
                progressiveLoadScrollMargin: 50,
                paginationSize: 100,
                ajaxURL: endpoint,
                ajaxParams: {},
                selectableRowsPersistence: false,
                ajaxRequestFunc: async (_url, _config, params) => {
                    const empty = createEmptyResponse(params.size || 100);
                    if (!isMounted) return Promise.resolve(empty);

                    const f = filtersRef.current;
                    const ep = extraParamsRef.current;
                    const u = new URLSearchParams();

                    // Extra params (ex: swapped)
                    Object.entries(ep).forEach(([k, v]) => u.append(k, String(v)));

                    // Date range
                    if (f.dateStart) u.append('startDate', f.dateStart);
                    if (f.dateEnd) u.append('endDate', f.dateEnd);

                    // Global search q
                    if (f.q && f.q.length >= minCharsFilter) u.append('q', f.q);

                    // Filter bar dropdowns → filter_*
                    if (f.judet) u.append('filter_judet', f.judet);
                    if (f.tipObj) u.append('filter_tip_obj', String(f.tipObj));
                    if (f.status) u.append('filter_status', f.status);

                    // Pagination
                    if (params.page) u.append('page', String(params.page));
                    if (params.size) u.append('rows', String(params.size));

                    // Sort
                    if (params.sort && params.sort.length > 0) {
                        u.append('sortField', params.sort[0].field);
                        u.append('sortOrder', params.sort[0].dir);
                    }

                    // Header column filters (păstrate din Tabulator)
                    let abort = false;
                    const rawFilters = (params as { filter?: unknown }).filter;
                    if (rawFilters && Array.isArray(rawFilters)) {
                        for (const ft of rawFilters as { field: string; value: unknown }[]) {
                            const value = ft.value;
                            if (value == null || value === '') continue;
                            if (ft.field === 'tip_obj') {
                                u.append('filter_tip_obj', String(value));
                                continue;
                            }
                            if (ft.field === 'awb' && awbRegexp) {
                                const rx = buildAnchoredRegex(awbRegexp);
                                if (!rx.test(String(value).trim())) {
                                    abort = true;
                                    continue;
                                }
                                u.append('filter_awb', String(value).trim());
                                continue;
                            }
                            if (typeof value === 'string') {
                                const v = value.trim();
                                if (v.length < minCharsFilter) { abort = true; continue; }
                                u.append(`filter_${ft.field}`, v);
                            } else if (typeof value === 'number') {
                                u.append(`filter_${ft.field}`, String(value));
                            }
                        }
                    }

                    if (abort) return Promise.resolve(empty);

                    try {
                        const axios = (await import('@/lib/axios')).default;
                        const res = await axios.get(`${endpoint}?${u.toString()}`, {
                            headers: { 'Content-Type': 'application/json' },
                        });
                        if (res.data?.success) return res.data;
                        return empty;
                    } catch (e) {
                        console.error('AwbTable ajax error', e);
                        return Promise.reject(e);
                    }
                },
                ajaxResponse: (_url, _params, response) => {
                    if (!response?.data) return { data: [], last_page: 1 };
                    return { data: response.data.data, last_page: response.data.last_page };
                },
                dataLoaderError: 'Eroare la încărcarea datelor',
                columns: memoColumns,
                headerFilterLiveFilterDelay: 600,
                filterMode: 'remote',
                sortMode: 'remote',
                layout: 'fitColumns',
                resizableColumnFit: true,
                placeholder: 'Nu au fost gasite AWB-uri.',
            });

            tabulatorRef.current.on('rowSelectionChanged', (data) => {
                if (onSelectionChange) onSelectionChange(data as AwbData[]);
            });

            if (onRowDblClick) {
                tabulatorRef.current.on('rowDblClick', (_e, row) => {
                    onRowDblClick(row.getData() as AwbData);
                });
            }

            tabulatorRef.current.on('dataLoaded', () => {
                const data = (tabulatorRef.current?.getData() as AwbData[]) || [];
                if (onDataLoaded) onDataLoaded({ displayed: data.length, total: 0 });
            });

            return () => {
                isMounted = false;
                if (tabulatorRef.current) {
                    tabulatorRef.current.destroy();
                    tabulatorRef.current = null;
                }
            };
        }
    }, [endpoint, memoColumns, awbRegexp, minCharsFilter, onSelectionChange, onRowDblClick, onDataLoaded]);

    // Granular refresh pe create/update/delete row
    useEffect(() => {
        if (!tabulatorRef.current || !createUpdateDeleteRow) return;
        const { rowId, newAwbData, action } = createUpdateDeleteRow;
        if (action === 'create' && newAwbData?.id !== undefined) {
            tabulatorRef.current.addRow(newAwbData, true).then((row) => row.reformat());
        } else if (action === 'update' && rowId !== undefined && newAwbData) {
            const row = tabulatorRef.current.getRow(rowId);
            if (row) row.update(newAwbData).then(() => row.reformat());
        } else if (action === 'delete' && rowId !== undefined) {
            const row = tabulatorRef.current.getRow(rowId);
            if (row) row.delete();
        } else if (action === 'bo') {
            tabulatorRef.current.setData();
        }
    }, [createUpdateDeleteRow]);

    // Print rows update
    useEffect(() => {
        if (!tabulatorRef.current || !printRows) return;
        const { rowIds, printed_by_user } = printRows;
        if (!rowIds || !printed_by_user) return;
        const printedAt = new Date().toISOString();
        rowIds.forEach((id) => {
            const row = tabulatorRef.current?.getRow(id);
            const data = row?.getData() as AwbData | undefined;
            if (row && data) {
                row.update({ ...data, can_update: false, printed_at: printedAt, printed_by_user });
                row.reformat();
                row.deselect();
            }
        });
        tabulatorRef.current?.deselectRow();
    }, [printRows]);

    return (
        <div
            ref={tableRef}
            className="w-full tabulator-container rounded-lg border border-border overflow-hidden bg-background"
        />
    );
}
