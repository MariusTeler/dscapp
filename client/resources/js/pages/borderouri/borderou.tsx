import { Card, CardContent } from '@/components/ui/primereact/card';
import { Dialog } from '@/components/ui/primereact/dialog';
import { useState, useRef, useEffect, useMemo, useCallback } from 'react';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import type { RowComponent, ColumnDefinition } from 'tabulator-tables';
import { data as borderouri } from '@/routes/slips';
import type { AwbData, BorderouData } from '@/types';
import { InfoAwb } from '@/components/info-awb';
import {
    loadData as loadBorderouri,
    baseColumns as borderuriColumns,
    actionsColumns as borderuriActionsColumn,
} from '@/services/borderouri/borderouri-service';

import {
    loadData as loadAwbs,
    baseColumns as awbsColumns,
    actionsColumns as awbsActionsColumns,
} from '@/services/borderouri/awbs-service';

interface BorderouListProps {
    onPrintBorderou: (borderou: BorderouData) => void;
    onPrintAwbs: (borderou: BorderouData) => void;
    onExportCsv: (borderou: BorderouData) => void;
    onPrint: (rowIds: number[]) => void;
}

export function BorderouList({ onPrintBorderou, onPrintAwbs, onExportCsv, onPrint }: BorderouListProps) {
    const [, setLoading] = useState(false);
    const [totalRecords, setTotalRecords] = useState(0);
    const [totalAfisate, setTotalAfisate] = useState(0);
    const tableRef = useRef<HTMLDivElement>(null);
    const tabulatorRef = useRef<Tabulator | null>(null);
    const expandedRowRef = useRef<RowComponent | null>(null);

    const [selectedAwb, setSelectedAwb] = useState<AwbData | null>(null);
    const [visible, setVisible] = useState(false);

    // Load AWBs for a borderou and create nested table
    const loadAwbsForBorderou = useCallback(async (row: RowComponent) => {
        const borderou = row.getData();
        const rowElement = row.getElement();
        
        // Find or create detail container
        let detailContainer = rowElement.querySelector('.tabulator-detail-container') as HTMLElement;
        if (!detailContainer) {
            detailContainer = document.createElement('div');
            detailContainer.className = 'tabulator-detail-container';
            rowElement.appendChild(detailContainer);
        }
        
        detailContainer.className = 'tabulator-detail-container p-2 bg-gray-50 dark:bg-gray-900';
        detailContainer.innerHTML = '<div class="flex items-center gap-2"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div><span class="text-sm">Se încarcă AWB-urile...</span></div>';

        try {
            const result = await loadAwbs({ id: borderou.id, page: 1, rows: 300 });

            if (!result) {
                throw new Error('Failed to load AWBs');
            }
            
            if (result.success && result.data && result.data.total > 0 && result.data.data) {
                // Create nested table container
                detailContainer.innerHTML = '<div class="awb-table-container"></div>';
                const container = detailContainer.querySelector('.awb-table-container') as HTMLElement;
                container.style.width = row.getElement().offsetWidth + 'px' || '800px';
                
                // Define AWB columns with footer calculations
                const awbBaseColumns = awbsColumns().map(col => {
                    if (col.field === 'expeditie') {
                        return {
                            ...col,
                            bottomCalc: 'count' as const,
                            bottomCalcFormatter: (cell: { getValue: () => number }) => {
                                const count = cell.getValue();
                                return `<strong>Total AWBs: ${count}</strong>`;
                            }
                        };
                    }
                    if (col.field === 'piese') {
                        return {
                            ...col,
                            bottomCalc: 'sum' as const,
                            bottomCalcFormatter: (cell: { getValue: () => number }) => {
                                const sum = cell.getValue();
                                return `<strong>${sum}</strong>`;
                            }
                        };
                    }
                    if (col.field === 'greutate') {
                        return {
                            ...col,
                            bottomCalc: 'sum' as const,
                            bottomCalcFormatter: (cell: { getValue: () => number }) => {
                                const sum = cell.getValue();
                                return `<strong>${sum.toFixed(2)} kg</strong>`;
                            }
                        };
                    }
                    return col;
                });
                
                const awbActions = awbsActionsColumns({
                    onPrint: onPrint,
                });

                // Create nested Tabulator
                new Tabulator(container, {
                    data: result.data.data,
                    columns: [...awbBaseColumns, awbActions],
                    layout: 'fitColumns',
                    height: '400',
                    sortMode: "local",
                    resizableColumnFit: true,
                    placeholder: 'Nu au fost gasite AWB-uri.',
                })
                .on("rowDblClick", (e: UIEvent, row: RowComponent) => {
                    const rowData = row.getData() as AwbData;
                    setSelectedAwb(rowData);
                    setVisible(true);
                });
            } else {
                detailContainer.innerHTML = '<div class="text-red-500">Eroare la încărcarea AWB-urilor</div>';
            }
        } catch (error) {
            console.error('Error loading AWBs:', error);
            detailContainer.innerHTML = '<div class="text-red-500">Eroare la încărcarea AWB-urilor</div>';
        }
    }, [onPrint]);

    // Memoized columns
    const columns = useMemo<ColumnDefinition[]>(() => {
        // Expand icon column
        const expandColumn: ColumnDefinition = {
            title: "",
            field: "_expand",
            width: 40,
            headerSort: false,
            resizable: false,
            formatter: (cell) => {
                const row = cell.getRow();
                const isOpen = row.getData()._expanded || false;
                console.log('isOpen:', isOpen);
                return isOpen ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>';
            },
            cellClick: (e, cell) => {
                const row = cell.getRow();
                const rowData = row.getData();
                const rowElement = row.getElement();
                
                // Close previously expanded row if different from current
                if (expandedRowRef.current && expandedRowRef.current !== row) {
                    const prevRowData = expandedRowRef.current.getData();
                    const prevRowElement = expandedRowRef.current.getElement();
                    if (prevRowElement) {
                        prevRowElement.classList.remove('tabulator-row-expanded');
                        const prevDetailContainer = prevRowElement.querySelector('.tabulator-detail-container');
                        if (prevDetailContainer) {
                            prevDetailContainer.remove();
                        }
                    }
                    expandedRowRef.current.update({ ...prevRowData, _expanded: false });
                    expandedRowRef.current.getCell('_expand').getElement().querySelector('svg')?.remove();
                    expandedRowRef.current.reformat();
                    expandedRowRef.current = null;
                }
                
                if (rowData._expanded) {
                    if (rowElement) {
                        rowElement.classList.remove('tabulator-row-expanded');
                        const detailContainer = rowElement.querySelector('.tabulator-detail-container');
                        if (detailContainer) {
                            detailContainer.remove();
                        }
                    }
                    row.update({ ...rowData, _expanded: false });
                    cell.getElement().querySelector('svg')?.remove();
                    row.reformat();
                    expandedRowRef.current = null;
                } else {
                    if (rowElement) {
                        rowElement.classList.add('tabulator-row-expanded');
                    }
                    row.update({ ...rowData, _expanded: true });
                    cell.getElement().querySelector('svg')?.remove();
                    row.reformat();
                    expandedRowRef.current = row;
                    loadAwbsForBorderou(row);
                }
            }
        };

        const base = borderuriColumns();
        const actions = borderuriActionsColumn({
            onPrintBorderou: onPrintBorderou,
            onPrintBoAwbs: onPrintAwbs,
            onExportCsv: onExportCsv,
        });
        return [expandColumn, ...base, actions];
    }, [onPrintBorderou, onPrintAwbs, onExportCsv, loadAwbsForBorderou]);

    // Tabulator initialization
    useEffect(() => {
        if (tableRef.current && !tabulatorRef.current) {
            console.log('Initializing Borderouri Tabulator...');

            tabulatorRef.current = new Tabulator(tableRef.current, {
                height: 750,
                progressiveLoad: "scroll",
                progressiveLoadScrollMargin:50,
                ajaxURL: borderouri().url,
                ajaxParams: {},
                paginationSize: 100,
                ajaxRequestFunc: async (url, config, params) => {
                    // Close previously expanded row if different from current
                    if (expandedRowRef && expandedRowRef.current) {
                        const prevRowData = expandedRowRef.current.getData();
                        const prevRowElement = expandedRowRef.current.getElement();
                        if (prevRowElement) {
                            prevRowElement.classList.remove('tabulator-row-expanded');
                            const prevDetailContainer = prevRowElement.querySelector('.tabulator-detail-container');
                            if (prevDetailContainer) {
                                prevDetailContainer.remove();
                            }
                        }
                        expandedRowRef.current.update({ ...prevRowData, _expanded: false });
                        expandedRowRef.current.getCell('_expand').getElement().querySelector('svg')?.remove();
                        expandedRowRef.current.reformat();
                        expandedRowRef.current = null;
                    }
                    setLoading(true);
                    tabulatorRef.current?.alert("Loading ...");
                    try {
                        const result = await loadBorderouri({
                            page: params.page || 1,
                            rows: params.size || 100,
                            sort: params.sort || [],
                            filters: params.filter || []
                        });
                        setLoading(false);
                        tabulatorRef.current?.clearAlert();
                        return result;
                    } catch (error) {
                        setLoading(false);
                        tabulatorRef.current?.clearAlert();
                        throw error;
                    }
                },
                ajaxResponse: (url, params, response) => {
                    setTotalRecords(response.data.total);
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page
                    };
                },
                columns: columns,
                headerFilterLiveFilterDelay: 600,
                filterMode: "remote",
                sortMode: "remote",
                layout: "fitColumns",
                resizableColumnFit: true,
                placeholder: "Nu au fost gasite borderouri."
            });

            tabulatorRef.current.on("dataLoaded", () => {
                const data = tabulatorRef.current?.getData() as BorderouData[] || [];
                setTotalAfisate(data.length);
            });
            tabulatorRef.current.on("dataFiltered", () => {
                const data = tabulatorRef.current?.getData() as BorderouData[] || [];
                setTotalAfisate(data.length);
            });
            tabulatorRef.current.on("dataChanged", () => {
                const data = tabulatorRef.current?.getData() as BorderouData[] || [];
                setTotalAfisate(data.length);
            });
        }

        return () => {
            if (tabulatorRef.current) {
                tabulatorRef.current.destroy();
                tabulatorRef.current = null;
            }
        };
    }, [columns]);

    return (
        <>
        <Card>
            <CardContent className="space-y-4">
                {/* Controls */}
                <div className="flex justify-between items-center">
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-medium">
                            Afisate: {totalAfisate} din {totalRecords}
                        </span>
                    </div>
                </div>

                <div
                    ref={tableRef}
                    className="w-full tabulator-container rounded-lg border border-border overflow-hidden bg-background"
                ></div>
            </CardContent>
        </Card>
        {/* AWB Details Dialog */}
        {visible && selectedAwb && (
            <Dialog
                visible={visible}
                onHide={() => setVisible(false)}
                header="Detalii AWB"
                modal={true}
                style={{ width: '50vw' }}
                breakpoints={{ '960px': '75vw', '641px': '100vw' }}
            >
                <InfoAwb
                    selectedAwb={selectedAwb}
                    onClose={() => setVisible(false)}
                    onPrint={onPrint ? (rowIds) => { setVisible(false); onPrint(rowIds); } : undefined}
                />
            </Dialog>
        )}
        </>
    );
}
