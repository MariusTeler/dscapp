import { Card, CardContent } from '@/components/ui/primereact/card';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { Plus, Upload, List } from 'lucide-react';
import { useState, useRef, useEffect, useCallback, useMemo } from 'react';
import { router } from '@inertiajs/react';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import type { ColumnDefinition, RowComponent } from 'tabulator-tables';
import { index, data } from '@/routes/recipients';
import { index as recipientsIndex } from '@/routes/recipients';
import {
    CreateDestinatarDialog,
    EditDestinatarDialog,
    DeleteDestinatarDialog,
    ViewDestinatarDialog
} from './destinatar';
import { Import } from '@/pages/destinatari/import';
import Toast, { ToastRef, showError, showSuccess, showWarn, type ToastMessageState } from '@/components/ui/primereact/toast';

import {
    loadData,
    baseColumns,
    actionsColumns,
    editDestinatar
} from '@/services/destinatari';
import type { DestinatarData } from '@/types';

export default function DestinariIndex() {
    // auth not used on this page

    // Toast ref
    const toast = useRef<ToastRef>(null);

    // Tab state
    const [activeTab, setActiveTab] = useState<string>("lista");

    // Tabulator state
    const [, setLoading] = useState(false);
    const [totalRecords, setTotalRecords] = useState(0);
    const [totalAfisate, setTotalAfisate] = useState(0);
    const tableRef = useRef<HTMLDivElement>(null);
    const tabulatorRef = useRef<Tabulator | null>(null);

    // Dialog state
    const [selected, setSelected] = useState<DestinatarData | null>(null);
    const [showDialog, setShowDialog] = useState(false);

    // CRUD Dialog states
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [showToast, setShowToast] = useState<ToastMessageState | null>(null);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [editingDestinatar, setEditingDestinatar] = useState<DestinatarData | null>(null);

    // Trigger toast when showToast changes
    useEffect(() => {
        if (showToast) {
            console.log('useEffect: showToast changed:', showToast);
            if(showToast.severity === 'success')
                showSuccess(toast, showToast.summary, showToast.detail || 'Operațiunea a fost realizată cu succes.');
            else if(showToast.severity === 'error')
                showError(toast, showToast.summary, showToast.detail || 'A apărut o eroare.');
            else if(showToast.severity === 'warn')
                showWarn(toast, showToast.summary, showToast.detail || 'Atentie!');
        }
    }, [showToast]);

    // Handle row actions
    const handleEdit = useCallback(async (DestinatarData: DestinatarData) => {
        try {
            const response = await editDestinatar(DestinatarData.id || 0);
            console.log('editDestinatar response:', response);
            if (response.success && response.data) {
                setEditingDestinatar(response.data);
                setShowEditDialog(true);
            } else {
                setShowToast({
                    severity: 'error',
                    summary: 'Eroare',
                    detail: response.message || 'Eroare la încărcarea datelor destinatarului.'
                });
            }
        } catch (error) {
            console.error('Error loading destinatar:', error);
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: error instanceof Error ? error.message : 'Eroare la încărcarea datelor destinatarului.'
            });
        }
    }, []);

    const handleDelete = useCallback((DestinatarData: DestinatarData) => {
        setSelected(DestinatarData);
        setShowDeleteDialog(true);
    }, []);

    // Import handlers
    const handleImportComplete = useCallback(() => {
        // Refresh the table after import
        router.visit(recipientsIndex().url, { preserveState: true });
    }, []);

    // Memoized dialog callbacks to prevent unnecessary re-renders
    const handleEditSuccess = useCallback(() => {
        setShowToast({
            severity: 'success',
            summary: 'Succes',
            detail: 'Destinatarul a fost editat cu succes.'
        });
        // Refresh table
        if (tabulatorRef?.current) {
            tabulatorRef.current.setData();
        }
    }, []);

    const handleEditFailed = useCallback((message: string) => {
        setShowToast({
            severity: 'error',
            summary: 'Eroare',
            detail: message || 'Eroare editare destinatar.'
        });
    }, []);

    const handleCreateSuccess = useCallback(() => {
        setShowToast({
            severity: 'success',
            summary: 'Succes',
            detail: 'Destinatarul a fost adaugat cu succes.'
        });
        // Refresh table
        if (tabulatorRef?.current) {
            tabulatorRef.current.setData();
        }
    }, []);

    const handleCreateFailed = useCallback((message: string) => {
        setShowToast({
            severity: 'error',
            summary: 'Eroare',
            detail: message || 'Eroare adaugare destinatar.'
        });
    }, []);

    const handleDeleteSuccess = useCallback(() => {
        setShowToast({
            severity: 'success',
            summary: 'Succes',
            detail: 'Destinatarul a fost sters cu succes.'
        });
        // Refresh table
        if (tabulatorRef?.current) {
            tabulatorRef.current.setData();
        }
    }, []);

    const handleDeleteFailed = useCallback((message: string) => {
        setShowToast({
            severity: 'error',
            summary: 'Eroare',
            detail: message || 'Eroare stergere destinatar.'
        });
    }, []);

    // Columns memoized to avoid changing identity across renders
    const columns: ColumnDefinition[] = useMemo(() => {
        const baseColumn = baseColumns();
        const actionsColumn = actionsColumns({ onEdit: handleEdit, onDelete: handleDelete });
        return [...baseColumn, actionsColumn];
    }, [handleEdit, handleDelete]);

    // Initialize Tabulator
    useEffect(() => {
        if (activeTab === "lista" && tableRef.current && !tabulatorRef.current) {
            console.log('useEffect: Initializing Tabulator...');
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
                    tabulatorRef.current?.alert("Loading ...");
                    setLoading(true);
                    try {
                        const loadParams = {
                            page: params.page || 1,
                            rows: params.size || 100,
                            sort: params.sort || [],
                            filters: params.filter || []
                        };
                        const result = await loadData(loadParams);
                        tabulatorRef.current?.clearAlert();
                        setLoading(false);
                        return result;
                    } catch (error) {
                        tabulatorRef.current?.clearAlert();
                        console.error('ajaxRequestFunc - error occurred:', error);
                        console.log('ajaxRequestFunc - setting loading to false due to error');
                        setLoading(false);
                        throw error;
                    }
                },
                columns: columns,
                headerFilterLiveFilterDelay: 600,
                filterMode: "remote",
                sortMode: "remote",
                layout: "fitColumns",
                resizableColumnFit: true,
                placeholder: "Nu au fost gasiti destinatari."
            });

            // Add row double-click event listener
            tabulatorRef.current.on("rowDblClick", (e: UIEvent, row: RowComponent) => {
                const DestinatarData = row.getData() as DestinatarData;
                setSelected(DestinatarData);
                setShowDialog(true);
            });

            tabulatorRef.current.on("dataLoaded", () => {
                const data = tabulatorRef.current?.getData() as DestinatarData[] || [];
                setTotalAfisate(data.length);
            });
            tabulatorRef.current.on("dataFiltered", () => {
                const data = tabulatorRef.current?.getData() as DestinatarData[] || [];
                setTotalAfisate(data.length);
            });
            tabulatorRef.current.on("dataChanged", () => {
                const data = tabulatorRef.current?.getData() as DestinatarData[] || [];
                setTotalAfisate(data.length);
            });

            console.log('useEffect: Tabulator instance created successfully');
        } 
        else {
            console.log('useEffect: Skipping Tabulator creation', {
                hasTableRef: !!tableRef.current,
                hasTabulatorRef: !!tabulatorRef.current
            });
        }

        return () => {
            console.log('useEffect cleanup: Destroying Tabulator instance');
            if (tabulatorRef.current) {
                tabulatorRef.current.destroy();
                tabulatorRef.current = null;
                console.log('useEffect cleanup: Tabulator instance destroyed');
            }
        };
    }, [activeTab, handleEdit, handleDelete, columns]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Destinatari', href: index.url() }
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Destinatari" />
            <Toast ref={toast} position="top-center" onShow={() => setShowToast(null)} />

            <div className="space-y-2">
                <Tabs defaultValue="lista" onValueChange={setActiveTab} className="w-full">
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="lista" className="flex items-center gap-2">
                            <List className="h-4 w-4" />
                            Lista
                        </TabsTrigger>
                        <TabsTrigger value="import" className="flex items-center gap-2">
                            <Upload className="h-4 w-4" />
                            Import
                        </TabsTrigger>
                    </TabsList>

                    {/* First Tab: Lista Destinatari */}
                    <TabsContent value="lista" className="space-y-4">
                        <Card>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <div className="flex items-center gap-4">
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm font-medium">
                                                Afisate: {totalAfisate} din {totalRecords}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            size="sm"
                                            onClick={() => setShowCreateDialog(true)}
                                            className="flex items-center gap-2"
                                        >
                                            <Plus className="h-4 w-4" />
                                            Adauga
                                        </Button>
                                    </div>
                                </div>

                                <div
                                    ref={tableRef}
                                    className="w-full tabulator-container rounded-lg border border-border overflow-hidden bg-background"
                                ></div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Second Tab: Import Destinatari */}
                    <TabsContent value="import" className="space-y-6">
                        <Import
                            onComplete={handleImportComplete}
                            onSwitchToList={() => router.visit(recipientsIndex().url)}
                        />
                    </TabsContent>
                </Tabs>
      
                {/* Destinatar Details Dialog */}
                {showDialog && selected && (
                    <ViewDestinatarDialog
                        visible={showDialog}
                        onVisibleChange={setShowDialog}
                        destinatar={selected}
                        onEdit={handleEdit}
                        onDelete={handleDelete}
                    />
                )}

                {/* Create Dialog */}
                {showCreateDialog && (
                    <CreateDestinatarDialog
                        visible={showCreateDialog}
                        onVisibleChange={setShowCreateDialog}
                        onSuccess={handleCreateSuccess}
                        onFailed={handleCreateFailed}
                    />
                )}

                {/* Edit Dialog */}
                {showEditDialog && editingDestinatar && (
                    <EditDestinatarDialog
                        visible={showEditDialog}
                        onVisibleChange={setShowEditDialog}
                        destinatar={editingDestinatar}
                        onSuccess={handleEditSuccess}
                        onFailed={handleEditFailed}
                    />
                )}

                {/* Delete Dialog */}
                {showDeleteDialog && selected && (
                    <DeleteDestinatarDialog
                        visible={showDeleteDialog}
                        onVisibleChange={setShowDeleteDialog}
                        destinatar={selected}
                        onSuccess={handleDeleteSuccess}
                        onFailed={handleDeleteFailed}
                    />
                )}
            </div>
        </AppLayout>
    );
}
