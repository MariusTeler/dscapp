import { Card, CardContent } from '@/components/ui/primereact/card';
import { Calendar } from '@/components/ui/primereact/calendar';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/primereact/dialog';
import { Download } from 'lucide-react';
import { useState, useRef, useEffect, useMemo } from 'react';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import type { RowComponent, ColumnDefinition } from 'tabulator-tables';
import { predate } from '@/routes/shipments';
import type { AwbData } from '@/types';
import Toast, { ToastRef, showSuccess, showError } from '@/components/ui/primereact/toast';
import { InfoAwb } from '@/components/info-awb';
import {
    loadData as loadAwbRetururi,
    baseColumns as awbRetururiColumns,
    exportRetururiCsv,
} from '@/services/shipments/retururi-service';

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

export function AwbRetururi() {
    const [, setLoading] = useState(false);
    const [totalRecords, setTotalRecords] = useState(0);
    const [totalAfisate, setTotalAfisate] = useState(0);
    const tableRef = useRef<HTMLDivElement>(null);
    const tabulatorRef = useRef<Tabulator | null>(null);
    const toast = useRef<ToastRef>(null);

    // Dialog state
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

    // Export CSV handler
    const handleExportCSV = async () => {
        try {
            await exportRetururiCsv(
                tabulatorRef.current?.getFilters() || [],
                startDate.toISOString().split('T')[0],
                endDate.toISOString().split('T')[0]
            );
            showSuccess(toast, 'Succes', 'Fisier CSV exportat cu succes!');
        } catch (error) {
            showError(toast, 'Eroare', error instanceof Error ? error.message : 'Eroare la exportul CSV');
        }
    };

    // Memoized columns
    const columns: ColumnDefinition[] = useMemo<ColumnDefinition[]>(() => {
            return awbRetururiColumns();
    }, []);

    // Tabulator initialization
    useEffect(() => {
        if (tableRef.current && !tabulatorRef.current) {
            let isMounted = true;

            tabulatorRef.current = new Tabulator(tableRef.current, {
                height: 750,
                progressiveLoad: "scroll",
                progressiveLoadScrollMargin:50,
                ajaxURL: predate().url,
                ajaxParams: {},
                paginationSize: 100,
                ajaxRequestFunc: async (url, config, params) => {
                    const emptyResponse = createEmptyTabulatorResponse(params.size || 100);

                    if (!isMounted || !validateDateRange(startDate, endDate)) {
                        return Promise.resolve(emptyResponse);
                    }
                    
                    setLoading(true);
                    tabulatorRef.current?.alert("Loading ...");
                    try {
                        // Format dates for server
                        const pad = (n: number) => String(n).padStart(2, '0');
                        const formatLocalDate = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        const formattedStartDate = formatLocalDate(startDate);
                        const formattedEndDate = formatLocalDate(endDate);

                        const result = await loadAwbRetururi({
                            page: params.page || 1,
                            rows: params.size || 100,
                            sort: params.sort || [],
                            filters: params.filter || [],
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
                placeholder: "Nu au fost gasite AWB-uri de retur."
            });

            // Add row double-click event listener
            tabulatorRef.current.on("rowDblClick", (e: UIEvent, row: RowComponent) => {
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

            return () => {
                isMounted = false;
                if (tabulatorRef.current) {
                    tabulatorRef.current.destroy();
                    tabulatorRef.current = null;
                }
            };
        }
    }, [columns, startDate, endDate]);

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
                            <Button
                                size="sm"
                                onClick={handleExportCSV}
                                disabled={totalRecords === 0}
                                className="gap-2"
                            >
                                <Download className="h-4 w-4" />
                                Export CSV
                            </Button>
                        </div>
                    </div>

                    {/* Tabulator Table */}
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
                    />
                </Dialog>
            )}
        </>
    );
}
