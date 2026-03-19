import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import OrderForm from '@/pages/comenzi/order-form';
import { ViewOrderDialog, EditOrderDialog } from '@/pages/comenzi/order';
import type { BreadcrumbItem, SharedData, OrderData } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { useState, useRef, useEffect, useCallback, useMemo } from 'react';
import { FileText, List } from 'lucide-react';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import type { ColumnDefinition, RowComponent } from 'tabulator-tables';
import { index, data } from '@/routes/orders';
import { editOrder, updateOrder, deleteOrder } from '@/services/comenzi';
import Toast, { ToastRef, showError, showSuccess, showWarn, type ToastMessageState } from '@/components/ui/primereact/toast';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';

import {
    loadData,
    baseColumns,
    actionsColumns,
} from '@/services/comenzi';

export default function ComenziIndex() {
    const { auth, pcs, prefs } = usePage<SharedData>().props;
    const [activeTab, setActiveTab] = useState<string>("create");
    
    // Toast ref
    const toast = useRef<ToastRef>(null);
    
    // Tabulator state
    const [, setLoading] = useState(false);
    const [totalRecords, setTotalRecords] = useState(0);
    const [totalAfisate, setTotalAfisate] = useState(0);
    const tableRef = useRef<HTMLDivElement>(null);
    const tabulatorRef = useRef<Tabulator | null>(null);

    // Dialog state
    const [loadingOrder, setLoadingOrder] = useState(false);
    const [selected, setSelected] = useState<OrderData | null>(null);
    const [editing, setEditing] = useState<OrderData | null>(null);
    const [showViewDialog, setShowViewDialog] = useState(false);
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [orderToDelete, setOrderToDelete] = useState<OrderData | null>(null);
    const [showToast, setShowToast] = useState<ToastMessageState | null>(null);

    const [updateRow, setUpdateRow] = useState({ rowId: undefined, order: null} as { rowId: number | undefined, order: OrderData | null});

    // Trigger toast when showToast changes
    useEffect(() => {
        if (showToast) {
            if(showToast.severity === 'success')
                showSuccess(toast, showToast.summary, showToast.detail || 'Operațiunea a fost realizată cu succes.');
            else if(showToast.severity === 'error')
                showError(toast, showToast.summary, showToast.detail || 'A apărut o eroare.');
            else if(showToast.severity === 'warn')
                showWarn(toast, showToast.summary, showToast.detail || 'Atentie!');
        }
    }, [showToast]);

    // Handle row actions
    const handleEdit = useCallback(async (order: OrderData) => {
        setLoadingOrder(prev => {
            // Prevent duplicate calls
            if (prev) return prev;
            return true;
        });
        
        try {
            const result = await editOrder(order.id || 0);
            
            if (result.success && result.data) {
                console.log('Loaded order data for editing:', result.data);
                setShowEditDialog(true);
                setEditing(result.data);
            } else {
                setShowEditDialog(false);
                setShowToast({
                    severity: 'error',
                    summary: 'Eroare',
                    detail: result.message || 'Eroare la încărcarea datelor comenzii.'
                });
            }
        } catch (error) {
            setShowEditDialog(false);
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: error instanceof Error ? error.message : 'Eroare la încărcarea datelor comenzii.'
            });
        } finally {
            setLoadingOrder(false);
        }
    }, []);

    const handleDelete = useCallback((rowData: OrderData) => {
        setOrderToDelete(rowData);
        setShowDeleteDialog(true);
    }, []);
    
    const handleConfirmDelete = useCallback(async () => {
        if (!orderToDelete) return;
        
        try {
            const success = await deleteOrder(orderToDelete.id || 0);
            
            if (success) {
                const deletedAt = new Date().toISOString();
                const updatedOrder: OrderData = {
                    ...orderToDelete,
                    status: 7,
                    data_status: deletedAt,
                    deleted_at: deletedAt,
                };

                setShowToast({
                    severity: 'success',
                    summary: 'Succes',
                    detail: 'Comanda a fost stearsa cu succes.'
                });
                setUpdateRow({ rowId: updatedOrder.id || undefined, order: updatedOrder });
            } else {
                setShowToast({
                    severity: 'error',
                    summary: 'Eroare',
                    detail: 'Eroare la stergerea comenzii.'
                });
            }
        } catch (error) {
            console.error('Error deleting order:', error);
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: error instanceof Error ? error.message : 'Eroare la stergerea comenzii.'
            });
        } finally {
            setOrderToDelete(null);
        }
    }, [orderToDelete]);

    const handleCreateSuccess = useCallback(() => {
        setShowToast({
            severity: 'success',
            summary: 'Succes',
            detail: 'Comanda a fost creata cu succes!'
        });
        // Switch to lista tab to see the new order
        // setActiveTab('lista');
        // Refresh table
        if (tabulatorRef?.current) {
            tabulatorRef.current.setData();
        }
    }, []);

    const handleCreateFailed = useCallback(() => {
        setShowToast({
            severity: 'error',
            summary: 'Eroare',
            detail: 'Eroare la crearea comenzii.'
        });
    }, []);

    const handleSave = useCallback(async (formData: OrderData) => {
        console.log('handleSave called with formData:', editing);
        if (!editing) return;
        if(!editing.id || editing.id === 0) return;
        
        try {
            const result = await updateOrder(editing.id, formData);
            if (result.success) {
                const updatedAt = new Date().toISOString();
                const updatedOrder: OrderData = {
                    ...editing,
                    status: 1,
                    data_status: updatedAt,
                    updated_at: updatedAt,
                    expeditor_nume: formData.ridica_de_la || editing.expeditor_nume,
                };

                setShowEditDialog(false);
                setEditing(null);
                setShowToast({
                    severity: 'success',
                    summary: 'Succes',
                    detail: result.message || 'Comanda a fost actualizată cu succes.'
                });
                setUpdateRow({ rowId: editing.id, order: updatedOrder});
            } else {
                setShowToast({
                    severity: 'error',
                    summary: 'Eroare',
                    detail: result.message || 'Eroare la actualizarea comenzii.'
                });
            }
        } catch (error) {
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: error instanceof Error ? error.message : 'Eroare la actualizarea comenzii.'
            });
        }
    }, [editing]);

    // Memoize columns to avoid identity changes across renders
    const columns: ColumnDefinition[] = useMemo(() => {
        const baseColumn = baseColumns();
        const actionsColumn = actionsColumns({ onEdit: handleEdit, onDelete: handleDelete });
        return [...baseColumn, actionsColumn];
    }, [handleEdit, handleDelete]);

    // Refresh table when refreshTrigger changes
    useEffect(() => {
        if (!tabulatorRef.current) {
            return;
        }
        const { rowId, order } = updateRow || {};
        if (rowId !== undefined) {
            console.log('Updating row with ID:', rowId, 'with data:', order);
            const row = tabulatorRef.current.getRow(rowId);
            if (row && order) {
                row.update(order);
            }
        }
    }, [updateRow]);

    // Initialize Tabulator
    useEffect(() => {
        console.log('useEffect triggered:', { 
            activeTab, 
            hasTableRef: !!tableRef.current, 
            hasTabulatorRef: !!tabulatorRef.current 
        });

        if (activeTab !== "lista") {
            console.log('useEffect: Not on lista tab, skipping');
            return;
        }

        if (!tableRef.current) {
            console.log('useEffect: tableRef not ready yet, skipping');
            return;
        }

        if (tabulatorRef.current) {
            console.log('useEffect: Tabulator already exists, skipping');
            return;
        }

        console.log('useEffect: Creating new Tabulator instance');
        console.log('useEffect: Using columns configuration:', columns);
        tabulatorRef.current = new Tabulator(tableRef.current, {
                height: 750,
                progressiveLoad: "scroll",
                progressiveLoadScrollMargin:50,
                paginationSize: 100,
                ajaxURL: data.url(),
                ajaxParams: {},
                ajaxResponse: (url, params, response) => {
                    setTotalRecords(response.data.total);
                    const returnData = {
                        data: response.data.data,
                        last_page: response.data.last_page
                    };
                    return returnData;
                },
                ajaxRequestFunc: async (url, config, params) => {
                    setLoading(true);
                    tabulatorRef.current?.alert("Loading ...");
                    try {
                        const loadParams = {
                            page: params.page || 1,
                            rows: params.size || 100,
                            sort: params.sort || [],
                            filters: params.filter || []
                        };
                        const result = await loadData(loadParams);
                        setLoading(false);
                        tabulatorRef.current?.clearAlert();
                        return result;
                    } catch (error) {
                        console.error('ajaxRequestFunc - error occurred:', error);
                        setLoading(false);
                        tabulatorRef.current?.clearAlert();
                        throw error;
                    }
                },
                columns: columns,
                headerFilterLiveFilterDelay: 600,
                filterMode: "remote",
                sortMode: "remote",
                layout: "fitColumns",
                resizableColumnFit: true,
                placeholder: "Nu au fost gasite comenzi."
            });

            // Add row double-click event listener
            tabulatorRef.current.on("rowDblClick", (e: UIEvent, row: RowComponent) => {
                const rowData = row.getData() as OrderData;
                setSelected(rowData);
                setShowViewDialog(true);
            });

            tabulatorRef.current.on("dataLoaded", () => {
                const data = tabulatorRef.current?.getData() as OrderData[] || [];
                setTotalAfisate(data.length);
            });
            tabulatorRef.current.on("dataFiltered", () => {
                const data = tabulatorRef.current?.getData() as OrderData[] || [];
                setTotalAfisate(data.length);
            });
            tabulatorRef.current.on("dataChanged", () => {
                const data = tabulatorRef.current?.getData() as OrderData[] || [];
                setTotalAfisate(data.length);
            });

            console.log('useEffect: Tabulator instance created successfully');
        
        return () => {
            console.log('useEffect cleanup: Destroying Tabulator instance');
            if (tabulatorRef.current) {
                tabulatorRef.current.destroy();
                tabulatorRef.current = null;
                console.log('useEffect cleanup: Tabulator instance destroyed');
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activeTab]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Comenzi', href: index.url() }
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Comenzi" />
            <Toast ref={toast} />

            <div className="space-y-2">
                <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="create" className="flex items-center gap-2">
                            <FileText className="h-4 w-4" />
                            Comanda noua
                        </TabsTrigger>
                        <TabsTrigger value="lista" className="flex items-center gap-2">
                            <List className="h-4 w-4" />
                            Lista
                        </TabsTrigger>
                    </TabsList>

                    {/* First Tab: Create Comanda */}
                    <TabsContent value="create" className="space-y-2">
                        <OrderForm 
                            auth={auth}
                            pcs={pcs}
                            prefs={prefs}
                            mode="create"
                            onSuccess={handleCreateSuccess}
                            onFailed={handleCreateFailed}
                        />
                    </TabsContent>

                    {/* Second Tab: Lista Comenzi */}
                    <TabsContent value="lista" className="space-y-2" forceMount>
                        <div style={{ display: activeTab === 'lista' ? 'block' : 'none' }}>
                            <Card>
                                <CardContent className="space-y-4">
                                    {/* Controls */}
                                    <div className="flex justify-between items-center">
                                        <div className="flex items-center gap-4">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-medium">
                                                    Afisate: {totalAfisate} din {totalRecords}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Tabulator Table */}
                                    <div
                                        ref={tableRef}
                                        className="w-full tabulator-container rounded-lg border border-border overflow-hidden bg-background"
                                    ></div>
                                </CardContent>
                            </Card>
                        </div>       
                    </TabsContent>
                </Tabs>

                {/* View Order Dialog */}
                {showViewDialog && selected && (
                    <ViewOrderDialog
                        visible={showViewDialog}
                        onVisibleChange={setShowViewDialog}
                        order={selected}
                        onEdit={handleEdit}
                        onDelete={handleDelete}
                    />
                )}

                {/* Edit Order Dialog */}
                {showEditDialog && !loadingOrder && editing && (
                    <EditOrderDialog
                        visible={showEditDialog}
                        onVisibleChange={setShowEditDialog}
                        order={editing}
                        auth={auth}
                        pcs={pcs}
                        onSave={handleSave}
                    />
                )}

                {/* Delete Order Dialog */}
                <ConfirmDialog
                    open={showDeleteDialog}
                    onOpenChange={setShowDeleteDialog}
                    title="Confirmare ștergere"
                    description={`Sunteți sigur că doriți să ștergeți comanda #${orderToDelete?.id}? Această acțiune nu poate fi anulată.`}
                    confirmLabel="Șterge"
                    cancelLabel="Anulează"
                    variant="destructive"
                    onConfirm={handleConfirmDelete}
                />
            </div>
        </AppLayout>
    );
}
