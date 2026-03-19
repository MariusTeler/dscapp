import { Card, CardContent } from '@/components/ui/primereact/card';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/primereact/dialog';
import { Calendar } from '@/components/ui/primereact/calendar';
import Toast, { ToastRef, showError } from '@/components/ui/primereact/toast';
import { CheckSquare, ArrowRightFromLine, Printer } from 'lucide-react';
import { useState, useRef, useEffect, useMemo } from 'react';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import type { ColumnDefinition } from 'tabulator-tables';
import { nepredate } from '@/routes/shipments';
import type { AwbData } from '@/types';
import { InfoAwb } from '@/components/info-awb';
import {
    loadData as loadAwbNepredate,
    baseColumns as awbNepredateColumns,
    actionsColumns as awbNepredateActionsColumns,
} from '@/services/shipments/nepredate-service';

const createEmptyTabulatorResponse = (rows = 100) => ({
    success: true,
    data: {
        data: [],
        total: 0,
        current_page: 1,
        per_page: rows,
        last_page: 1,
        from: null,
        to: null,
    },
});

interface AwbNepredateProps {
    mode: 'borderouri' | 'shipments';
    // Selection mode props
    onSelected?: (awbIds: number[]) => void;
    onPrinted?: (awbIds: number[]) => void;
    onCreate?: () => void;
    isCreating?: boolean;
    isPrinting?: boolean;
    // Actions mode props
    onEdit?: (awb: AwbData) => void;
    onPrint?: (rowIds: number[]) => void;
    onDelete?: (awb: AwbData) => void;
    // Common props
    createUpdateDeleteRow?: { rowId: number | undefined; newAwbData: AwbData | null; action: 'create' | 'update' | 'delete' | 'bo' | null };
    printRows?: { rowIds: number[]; printed_by: number | null } | null;
}

export function AwbNepredate({ 
    mode,
    onSelected,
    onPrinted,
    onCreate,
    isCreating = false,
    isPrinting = false,
    onEdit,
    onPrint,
    onDelete,
    createUpdateDeleteRow,
    printRows,
}: AwbNepredateProps) {
    const [selectedRows, setSelectedRows] = useState<AwbData[]>([]);
    const [, setLoading] = useState(false);
    const [totalAfisate, setTotalAfisate] = useState(0);
    const [totalRecords, setTotalRecords] = useState(0);
    const tableRef = useRef<HTMLDivElement>(null);
    const tabulatorRef = useRef<Tabulator | null>(null);
    const toast = useRef<ToastRef>(null);

    // Dialog state (for actions)
    const [selectedAwb, setSelectedAwb] = useState<AwbData | null>(null);
    const [visible, setVisible] = useState(false);

    // Date range state - default to last 1 month
    const [startDate, setStartDate] = useState<Date>(() => {
        const date = new Date();
        date.setMonth(date.getMonth() - 1);
        return date;
    });
    const [endDate, setEndDate] = useState<Date>(new Date());

    // Validate date range (max 3 months)
    const validateDateRange = (start: Date, end: Date): boolean => {
        const diffMs = end.getTime() - start.getTime(); // difference in milliseconds
        const diffDays = diffMs / (1000 * 60 * 60 * 24);
        
        return diffDays <= 90; // 3 months
    };

    const handleStartDateChange = (e: unknown) => {
        const eventValue = (e as { value: Date | null }).value;
        if (!eventValue) return;
        
        setStartDate(eventValue);
        if (!validateDateRange(eventValue, endDate)) {
            showError(toast, 'Eroare', 'Intervalul de date nu poate depăși 90 de zile.');
            return;
        }
        
        // Refresh table when both dates are set
        if (tabulatorRef.current) {
            tabulatorRef.current.setData();
        }
    };

    const handleEndDateChange = (e: unknown) => {
        const eventValue = (e as { value: Date | null }).value;
        if (!eventValue) return;
        
        setEndDate(eventValue);
        if (!validateDateRange(startDate, eventValue)) {
            showError(toast, 'Eroare', 'Intervalul de date nu poate depăși 90 de zile.');
            return;
        }
        
        // Refresh table when both dates are set
        if (tabulatorRef.current) {
            tabulatorRef.current.setData();
        }
    };

    // Refresh table when refreshTrigger changes
    useEffect(() => {
        if (!tabulatorRef.current) {
            return;
        }
        const { rowId, newAwbData, action } = createUpdateDeleteRow || {};
        if (action === 'create') {
            console.log('Creating row with ID:', newAwbData?.id, 'with data:', newAwbData);
            //create row tabulator
            if (newAwbData && newAwbData.id !== undefined) {
                tabulatorRef.current.addRow(newAwbData, true).then((row) => {
                    row.reformat();
                });
            }
        } else if (action === 'update' && rowId !== undefined) {
            console.log('Updating row with ID:', rowId, 'with data:', newAwbData);
            const row = tabulatorRef.current.getRow(rowId);
            if (row && newAwbData && newAwbData.id !== undefined) {
                row.update(newAwbData).then(() => {
                    row.reformat();
                });
            }
        } else if (action === 'delete' && rowId !== undefined) {
            console.log('Deleting row with ID:', rowId);
            const row = tabulatorRef.current.getRow(rowId);
            if (row) {
                row.delete();
            }
        } else if (action === 'bo') {
            console.log('Borderou created, refreshing table');
            tabulatorRef.current.setData();
        }
    }, [mode, createUpdateDeleteRow]);

    useEffect(() => {
        if (!tabulatorRef.current) {
            return;
        }
        const { rowIds, printed_by } = printRows || {};
        if (rowIds && printed_by) {
            const printedAt = new Date().toISOString();
            console.log('Print trigger for rows:', rowIds, ' printed_by :', printed_by);
            // Call the onPrint callback for each row ID
            rowIds.forEach(id => {
                const row = tabulatorRef.current?.getRow(id);
                const rowData = row?.getData() as AwbData | undefined;
                if (row && rowData) {
                    row.update({
                        ...rowData,
                        can_update: false,
                        printed_at: printedAt,
                        printed_by: printed_by,
                    });
                    row.reformat();
                    row.deselect(); // Deselect row after printing
                }
            });
            // Deselect all via Tabulator to keep React state in sync
            tabulatorRef.current?.deselectRow();
        }
    }, [printRows]);

    // Memoized columns
    const columns = useMemo<ColumnDefinition[]>(() => {
        const base = awbNepredateColumns();
        const actions = awbNepredateActionsColumns({
            onEdit: onEdit || (() => {}),
            onPrint: onPrint || (() => {}),
            onDelete: onDelete || (() => {})
        });
        return [...base, actions];
    }, [onEdit, onPrint, onDelete]);

    // Handle create borderou button click (selection)
    const handleCreateClick = () => {
        if (onSelected && selectedRows.length > 0) {
            const awbIds = selectedRows.map(row => row.id).filter((id): id is number => typeof id === 'number');
            onSelected(awbIds);
        }
    };

    // Handle printare multipla button click (selection)
    const handlePrintareSelectieClick = () => {
        if (onPrinted && selectedRows.length > 0) {
            const awbIds = selectedRows.map(row => row.id).filter((id): id is number => typeof id === 'number');
            onPrinted(awbIds);
        }
    };

    // Tabulator initialization
    useEffect(() => {
        if (tableRef.current && !tabulatorRef.current) {
            let isMounted = true;

            tabulatorRef.current = new Tabulator(tableRef.current, {
                height: 750,
                progressiveLoad: "scroll",
                progressiveLoadScrollMargin:50,
                paginationSize: 100,
                ajaxURL: nepredate().url,
                ajaxParams: {},
                selectableRowsPersistence: false,
                ajaxRequestFunc: async (url, config, params) => {
                    const emptyResponse = createEmptyTabulatorResponse(params.size || 100);

                    if (!isMounted || !validateDateRange(startDate, endDate)) {
                        return Promise.resolve(emptyResponse);
                    }
                    
                    setLoading(true);
                    tabulatorRef.current?.alert("Loading ...");
                    try {
                        // Format dates using local components to avoid UTC offset shifting the day
                        const pad = (n: number) => String(n).padStart(2, '0');
                        const formatLocalDate = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        const formattedStartDate = formatLocalDate(startDate);
                        const formattedEndDate = formatLocalDate(endDate);

                        const result = await loadAwbNepredate({
                            page: params.page || 1,
                            rows: params.size || 100,
                            sort: params.sort || [],
                            filters: params.filter || [],
                            swapped: mode === 'borderouri' ? false : true,
                            startDate: formattedStartDate,
                            endDate: formattedEndDate
                        });
                        
                        if (!isMounted) {
                            return Promise.resolve(emptyResponse);
                        }
                        
                        setLoading(false);
                        tabulatorRef.current?.clearAlert();
                        return result ?? emptyResponse;
                    } catch (error) {
                        if (!isMounted) {
                            return Promise.resolve(emptyResponse);
                        }
                        
                        setLoading(false);
                        tabulatorRef.current?.clearAlert();
                        console.error('Error loading data for Tabulator:', error);
                        const errorMessage = error instanceof Error ? error.message : 'Unknown error';
                        return Promise.reject(errorMessage);
                    }
                },
                ajaxResponse: (url, params, response) => {
                    if (!isMounted) return { data: [], last_page: 1 };

                    if (!response?.data) {
                        setTotalRecords(0);
                        return { data: [], last_page: 1 };
                    }

                    setTotalRecords(response.data.total);
                    return {
                        data: response.data.data,
                        last_page: response.data.last_page
                    };
                },
                dataLoaderError: "Eroare la incarcarea datelor",
                columns: columns,
                headerFilterLiveFilterDelay: 600,
                filterMode: "remote",
                sortMode: "remote",
                layout: "fitColumns",
                resizableColumnFit: true,
                placeholder: "Nu au fost gasite AWB-uri nepredate.",
            });

            // Add row double-click event listener for actions
            tabulatorRef.current.on("rowDblClick", (_e, row) => {
                const rowData = row.getData() as AwbData;
                setSelectedAwb(rowData);
                setVisible(true);
            });

            tabulatorRef.current.on("dataLoaded", () => {
                const data = tabulatorRef.current?.getData() as AwbData[] || [];
                setTotalAfisate(data.length);
            });
            tabulatorRef.current.on("dataFiltered", () => {
                const data = tabulatorRef.current?.getData() as AwbData[] || [];
                setTotalAfisate(data.length);
            });
            tabulatorRef.current.on("dataChanged", () => {
                const data = tabulatorRef.current?.getData() as AwbData[] || [];
                setTotalAfisate(data.length);
            });

            tabulatorRef.current.on("rowSelectionChanged", (data) => {
                const rows = data as AwbData[];
                setSelectedRows(rows);
            });
                

            return () => {
                isMounted = false;
                if (tabulatorRef.current) {
                    tabulatorRef.current.destroy();
                    tabulatorRef.current = null;
                }
            };
        }
    }, [columns, mode, startDate, endDate]);



    return (
        <>
            <Toast ref={toast} />
            <Card>
                <CardContent className="space-y-4">
                    {/* Controls */}
                    <div className="flex justify-between items-center gap-4">
                        <div className="flex items-center gap-2">
                            <span className="text-sm font-medium">
                                Afisate: {totalAfisate} din {totalRecords}
                            </span>
                            <span className="text-sm font-medium text-blue-600">
                                Selectate: {selectedRows.length}
                            </span>
                        </div>
                        
                        {/* Date Range Filter */}
                        <div className="flex items-center gap-2">
                            <div className="flex items-center gap-1">
                                <Calendar
                                    id="startDate"
                                    value={startDate}
                                    onChange={handleStartDateChange}
                                    readOnlyInput
                                    showIcon
                                    dateFormat="dd.mm.yy"
                                    placeholder="Data start"
                                    className="w-auto"
                                    maxDate={endDate}
                                    showButtonBar
                                />
                                <span className="text-sm text-gray-500">-</span>
                                <Calendar
                                    id="endDate"
                                    value={endDate}
                                    onChange={handleEndDateChange}
                                    readOnlyInput
                                    showIcon
                                    dateFormat="dd.mm.yy"
                                    placeholder="Data final"
                                    className="w-auto"
                                    minDate={startDate}
                                    maxDate={new Date()}
                                    showButtonBar
                                />
                            </div>
                        </div>

                        <div className="flex gap-2">
                            {onCreate && (
                                <Button
                                    size="sm"
                                    onClick={onCreate}
                                    className="flex items-center gap-2"
                                >
                                    <ArrowRightFromLine className="h-4 w-4" />
                                    Awb nou
                                </Button>
                            )}
                            <Button
                                size="sm"
                                onClick={handlePrintareSelectieClick}
                                disabled={selectedRows.length === 0 || isPrinting}
                                className="flex items-center gap-2"
                            >
                                <Printer className="h-4 w-4" />
                                {isPrinting ? 'Se printeaza...' : `Print (${selectedRows.length})`}
                            </Button>
                            {mode === 'borderouri' && (
                                <Button
                                    size="sm"
                                    onClick={handleCreateClick}
                                    disabled={selectedRows.length === 0 || isCreating}
                                    className="flex items-center gap-2"
                                >
                                    <CheckSquare className="h-4 w-4" />
                                    {isCreating ? 'Se creeaza...' : `Creaza Borderou (${selectedRows.length})`}
                                </Button>
                            )}
                        </div>
                    </div>
                    <div
                        ref={tableRef}
                        className="w-full tabulator-container rounded-lg border border-border overflow-hidden bg-background"
                    ></div>
                </CardContent>
            </Card>

            {/* Dialog for actions */}
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
                        onEdit={onEdit ? (awb) => { setVisible(false); onEdit(awb); } : undefined}
                        onPrint={onPrint ? (rowIds) => { setVisible(false); onPrint(rowIds); } : undefined}
                        onDelete={onDelete ? (awb) => { setVisible(false); onDelete(awb); } : undefined}
                        onClose={() => setVisible(false)}
                    />
                </Dialog>
            )}
        </>
    );
}
