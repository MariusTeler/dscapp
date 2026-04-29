
import { useState, useCallback, useEffect, useRef } from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { SharedData, BreadcrumbItem, AwbData, UserPrefs } from '@/types';
import type { ShipmentsTab } from '@/types/shipments';
import { index as shipmentsIndex } from '@/routes/shipments';
import Toast, { ToastRef, showError, showSuccess, type ToastMessageState } from '@/components/ui/primereact/toast';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Dialog } from '@/components/ui/primereact/dialog';
import { Button } from '@/components/ui/button';
import { ArrowRightFromLine } from 'lucide-react';
import AwbForm from '@/pages/awb/awb-form';
import { EditAwbDialog } from '@/pages/shipments/edit-awb-dialog';
import { createAwb, deleteAwb, getById, updateAwb } from '@/services/awbs';
import { useAwbPrint } from '@/hooks/use-awb-print';

import { ShipmentsHeader } from '@/pages/shipments/shipments-header';
import { ShipmentsFilterBar } from '@/pages/shipments/shipments-filter-bar';
import { useShipmentsFilters } from '@/pages/shipments/hooks/use-shipments-filters';
import { useShipmentsStats } from '@/pages/shipments/hooks/use-shipments-stats';
import { NepredateTab } from '@/pages/shipments/tabs/nepredate-tab';

import { PredateTab, PredateExportButton } from '@/pages/shipments/tabs/predate-tab';
import { RetururiTab, RetururiExportButton } from '@/pages/shipments/tabs/retururi-tab';

export default function ShipmentsIndex() {
    const { auth, pcs, prefs } = usePage<SharedData>().props;
    const toast = useRef<ToastRef>(null);

    const [activeTab, setActiveTab] = useState<ShipmentsTab>('nepredate');
    const { filters, setFilters } = useShipmentsFilters();

    const { stats } = useShipmentsStats(activeTab, filters);

    // Refresh triggers (din vechiul flow)
    const [createUpdateDeleteRow, setCreateUpdateDeleteRow] = useState({ rowId: undefined, newAwbData: null, action: null } as { rowId: number | undefined, newAwbData: AwbData | null, action: 'create' | 'update' | 'delete' | 'bo' | null });
    const [printRows, setPrintRows] = useState({ rowIds: [], printed_by_user: null } as { rowIds: number[]; printed_by_user: string | null });

    // Dialogs state
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [editingAwb, setEditingAwb] = useState<AwbData | null>(null);
    const [loadingAwb, setLoadingAwb] = useState(false);
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [awbToDelete, setAwbToDelete] = useState<AwbData | null>(null);
    const [showToast, setShowToast] = useState<ToastMessageState | null>(null);

    const { printing, handlePrintSelected, AwbPrintDialog } = useAwbPrint({
        prefs,
        userId: auth.user.id,
        onToast: setShowToast,
        onPrinted: (rowIds) => setPrintRows({ rowIds, printed_by_user: auth.user.user }),
    });

    useEffect(() => {
        if (showToast) {
            if (showToast.severity === 'success') showSuccess(toast, showToast.summary, showToast.detail || 'Operațiune reușită.');
            else if (showToast.severity === 'error') showError(toast, showToast.summary, showToast.detail || 'A apărut o eroare.');
        }
    }, [showToast]);

    // Edit flow
    const handleEditAwb = useCallback(async (awb: AwbData) => {
        setLoadingAwb(true);
        setShowEditDialog(true);
        const result = await getById(awb.id || 0);
        setLoadingAwb(false);
        if (result.success && result.data) {
            setEditingAwb(result.data);
        } else {
            setShowToast({ severity: 'error', summary: 'Eroare', detail: result.message || 'Eroare la încărcarea AWB.' });
            setShowEditDialog(false);
        }
    }, []);

    const handleCreateAwb = useCallback(async (formData: AwbData) => {
        const result = await createAwb(formData);
        if (result.success) {
            setShowToast({ severity: 'success', summary: 'Succes', detail: result.message || `AWB-ul ${result.data?.id || ''} creat!` });
            setShowCreateDialog(false);
            setCreateUpdateDeleteRow({ rowId: undefined, newAwbData: result.data as AwbData || null, action: 'create' });
            return;
        }
        setShowToast({ severity: 'error', summary: 'Eroare', detail: result.message || 'Eroare la crearea AWB.' });
    }, []);

    const handleUpdateAwb = useCallback(async (formData: AwbData) => {
        if (!editingAwb) return;
        const result = await updateAwb(editingAwb.id, formData);
        if (result.success) {
            setShowToast({ severity: 'success', summary: 'Succes', detail: result.message || 'AWB actualizat!' });
            setShowEditDialog(false);
            setEditingAwb(null);
            setCreateUpdateDeleteRow({ rowId: editingAwb.id, newAwbData: result.data as AwbData || null, action: 'update' });
        } else {
            setShowToast({ severity: 'error', summary: 'Eroare', detail: result.message || 'Eroare actualizare AWB.' });
        }
    }, [editingAwb]);

    const handleDeleteAwb = useCallback((awb: AwbData) => {
        setAwbToDelete(awb);
        setShowDeleteConfirm(true);
    }, []);

    const handleConfirmDelete = useCallback(async () => {
        if (!awbToDelete) return;
        const result = await deleteAwb(awbToDelete.id || 0);
        if (result.success) {
            setShowToast({ severity: 'success', summary: 'Succes', detail: result.message || 'AWB șters!' });
            setCreateUpdateDeleteRow({ rowId: awbToDelete.id, newAwbData: null, action: 'delete' });
        } else {
            setShowToast({ severity: 'error', summary: 'Eroare', detail: result.message || 'Eroare la ștergere.' });
        }
        setAwbToDelete(null);
    }, [awbToDelete]);

    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Liste expeditii', href: shipmentsIndex().url }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Liste expeditii" />

            <ShipmentsHeader
                activeTab={activeTab}
                onTabChange={setActiveTab}
                tabCounts={{ nepredate: null, predate: null, retururi: null }}
                stats={stats}
            />

            {activeTab === 'nepredate' && (
                <>
                    <ShipmentsFilterBar
                        tab="nepredate"
                        filters={filters}
                        onChange={setFilters}
                        actions={
                            <Button size="sm" onClick={() => setShowCreateDialog(true)} className="text-xs">
                                <ArrowRightFromLine className="h-3.5 w-3.5 mr-1" />
                                AWB nou
                            </Button>
                        }
                    />
                    <NepredateTab
                        filters={filters}
                        prefs={prefs as UserPrefs}
                        isPrinting={printing}
                        createUpdateDeleteRow={createUpdateDeleteRow}
                        printRows={printRows}
                        onEditAwb={handleEditAwb}
                        onPrintRows={handlePrintSelected}
                        onDeleteAwb={handleDeleteAwb}
                    />
                </>
            )}

            {activeTab === 'predate' && (
                <>
                    <ShipmentsFilterBar
                        tab="predate"
                        filters={filters}
                        onChange={setFilters}
                        actions={<PredateExportButton filters={filters} />}
                    />
                    <PredateTab filters={filters} prefs={prefs as UserPrefs} />
                </>
            )}

            {activeTab === 'retururi' && (
                <>
                    <ShipmentsFilterBar
                        tab="retururi"
                        filters={filters}
                        onChange={setFilters}
                        actions={<RetururiExportButton filters={filters} />}
                    />
                    <RetururiTab filters={filters} prefs={prefs as UserPrefs} />
                </>
            )}

            {/* Dialogs */}
            {loadingAwb && showEditDialog && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white p-6 rounded-lg">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto" />
                        <p className="mt-4">Se încarcă...</p>
                    </div>
                </div>
            )}

            {!loadingAwb && showEditDialog && editingAwb && (
                <EditAwbDialog
                    visible={showEditDialog && !loadingAwb}
                    onVisibleChange={setShowEditDialog}
                    awb={editingAwb}
                    auth={auth}
                    pcs={pcs}
                    prefs={prefs}
                    onUpdate={handleUpdateAwb}
                />
            )}

            {showCreateDialog && (
                <Dialog
                    visible={showCreateDialog}
                    onHide={() => setShowCreateDialog(false)}
                    header="AWB nou"
                    modal
                    maximized
                >
                    <AwbForm
                        auth={auth}
                        pcs={pcs}
                        prefs={prefs}
                        mode="create-from-shipment"
                        onCreate={handleCreateAwb}
                        onCancel={() => setShowCreateDialog(false)}
                    />
                </Dialog>
            )}

            <Toast ref={toast} />
            {AwbPrintDialog}

            <ConfirmDialog
                open={showDeleteConfirm}
                onOpenChange={setShowDeleteConfirm}
                title="Confirmare ștergere"
                description={`Sunteți sigur că doriți să ștergeți AWB ${awbToDelete?.awb}?`}
                confirmLabel="Șterge"
                cancelLabel="Anulează"
                variant="destructive"
                onConfirm={handleConfirmDelete}
            />
        </AppLayout>
    );
}
