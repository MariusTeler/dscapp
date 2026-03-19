
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { SharedData, BreadcrumbItem, AwbData } from '@/types';
import { index as shipmentsIndex, nepredate, predate, retururi } from '@/routes/shipments';
import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { PinIcon, LucideUnlockKeyhole, LucideLockKeyhole } from 'lucide-react';
import { useState, useCallback, useRef, useEffect } from 'react';
import { createAwb, deleteAwb, getById, updateAwb } from '@/services/awbs';
import { AwbPredate } from '@/pages/shipments/awb-predate';
import { AwbRetururi } from '@/pages/shipments/awb-retururi';
import { EditAwbDialog } from '@/pages/shipments/edit-awb-dialog';
import { Dialog } from '@/components/ui/primereact/dialog';
import AwbForm from '@/pages/awb/awb-form';
import Toast, { ToastRef, showError, showSuccess, type ToastMessageState } from '@/components/ui/primereact/toast';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { AwbNepredate } from './awb-nepredate';
import { useAwbPrint } from '@/hooks/use-awb-print';

export default function ShipmentsIndex() {
    const { auth, pcs, prefs } = usePage<SharedData>().props;
    const toast = useRef<ToastRef>(null);
    const [activeTab, setActiveTab] = useState('nepredate');
    // Refresh triggers
    const [createUpdateDeleteRow, setCreateUpdateDeleteRow] = useState({ rowId: undefined, newAwbData: null, action: null } as { rowId: number | undefined, newAwbData: AwbData | null, action: 'create' | 'update' | 'delete' | 'bo' | null });
    const [printRows, setPrintRows] = useState({ rowIds: [], printed_by: null } as { rowIds: number[]; printed_by: number | null });
    // Edit dialog state
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [editingAwb, setEditingAwb] = useState<AwbData | null>(null);
    const [loadingAwb, setLoadingAwb] = useState(false);
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    
    // Toast state
    const [showToast, setShowToast] = useState<ToastMessageState | null>(null);

    const { printing, handlePrintSelected, AwbPrintDialog } = useAwbPrint({
        prefs,
        userId: auth.user.id,
        onToast: setShowToast,
        onPrinted: (rowIds, printedBy) => setPrintRows({ rowIds, printed_by: printedBy }),
    });
    
    // Delete confirmation dialog state
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [awbToDelete, setAwbToDelete] = useState<AwbData | null>(null);
    
    // Trigger toast when showToast changes
    useEffect(() => {
        if (showToast) {
            if(showToast.severity === 'success')
                showSuccess(toast, showToast.summary, showToast.detail || 'Operațiunea a fost realizată cu succes.');
            else if(showToast.severity === 'error')
                showError(toast, showToast.summary, showToast.detail || 'A apărut o eroare.');
        }
    }, [showToast]);

    // Handle row actions
    const handleEditNepredate = useCallback(async (awb: AwbData) => {
        setLoadingAwb(true);
        setShowEditDialog(true); // Show dialog with loading state
        
        const result = await getById(awb.id || 0);
        setLoadingAwb(false);
        
        if (result.success && result.data) {
            console.log('Loaded AWB data for editing:', result.data);
            setEditingAwb(result.data);
        } else {
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: result.message || 'Eroare la încărcarea datelor AWB.'
            });
            setShowEditDialog(false);
        }
    }, []);

    const handleCreateAwb = useCallback(async (formData: AwbData) => {
        const result = await createAwb(formData);

        if (result.success) {
            setShowToast({
                severity: 'success',
                summary: 'Succes',
                detail: result.message || `AWB-ul ${result.data?.id || ''} a fost creat cu succes!`
            });
            setShowCreateDialog(false);
            // Trigger table refresh after successful create
            setCreateUpdateDeleteRow({ rowId: undefined, newAwbData: result.data as AwbData || null, action: 'create' });
            return;
        }

        setShowToast({
            severity: 'error',
            summary: 'Eroare',
            detail: result.message || 'Eroare la crearea AWB.'
        });
    }, []);

    const handleUpdateAwb = useCallback(async (formData: AwbData) => {
        if (!editingAwb) return;
        const result = await updateAwb(editingAwb.id, formData);
        console.log('Update AWB result:', result);
        if (result.success) {
            setShowToast({
                severity: 'success',
                summary: 'Succes',
                detail: result.message || 'AWB actualizat cu succes!'
            });
            setShowEditDialog(false);
            setEditingAwb(null);
            setCreateUpdateDeleteRow({ rowId: editingAwb.id, newAwbData: result.data as AwbData || null, action: 'update' });

        } else {
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: result.message || 'Eroare actualizare AWB.'
            });
        }
    }, [editingAwb]);

    const handleDeleteNepredate = useCallback(async (awb: AwbData) => {
        setAwbToDelete(awb);
        setShowDeleteConfirm(true);
    }, []);
    
    const handleConfirmDelete = useCallback(async () => {
        if (!awbToDelete) return;

        const result = await deleteAwb(awbToDelete.id || 0);
        if (result.success) {
            setShowToast({
                severity: 'success',
                summary: 'Succes',
                detail: result.message || 'AWB șters cu succes!'
            });
            setCreateUpdateDeleteRow({ rowId: awbToDelete.id, newAwbData: null, action: 'delete' });
        } else {
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: result.message || 'Eroare la ștergerea AWB.'
            });
        }
        setAwbToDelete(null);
    }, [awbToDelete]);


    
    // Generate breadcrumbs based on active tab
    const getBreadcrumbForTab = (tabValue: string): BreadcrumbItem[] => {
        const baseBreadcrumbs: BreadcrumbItem[] = [
            { title: 'Liste expeditii', href: shipmentsIndex().url }
        ];

        switch (tabValue) {
            case 'predate':
                return [...baseBreadcrumbs, { title: 'AWB-uri predate', href: predate().url }];
            case 'retururi':
                return [...baseBreadcrumbs, { title: 'AWB-uri retururi', href: retururi().url }];
            default:
                return [...baseBreadcrumbs, { title: 'AWB-uri nepredate', href: nepredate().url }];
        }
    };

    const breadcrumbs: BreadcrumbItem[] = getBreadcrumbForTab(activeTab);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Liste expeditii" />

            <div className="space-y-2">
                {/* Tabs */}
                <Tabs defaultValue="nepredate" onValueChange={setActiveTab}>
                    <TabsList className="grid w-full grid-cols-3">
                        <TabsTrigger value="nepredate" className="flex items-center gap-2">
                            <LucideUnlockKeyhole className="h-4 w-4" />
                            AWB-uri nepredate
                        </TabsTrigger>
                        <TabsTrigger value="predate" className="flex items-center gap-2">
                            <LucideLockKeyhole className="h-4 w-4" />
                            AWB-uri predate
                        </TabsTrigger>
                        <TabsTrigger value="retururi" className="flex items-center gap-2">
                            <PinIcon className="h-4 w-4" />
                            AWB-uri de retur
                        </TabsTrigger>
                    </TabsList>

                    {/* AWB-uri nepredate */}
                    <TabsContent value="nepredate">
                        <AwbNepredate
                            mode="shipments"
                            createUpdateDeleteRow={createUpdateDeleteRow}
                            printRows={printRows}
                            isPrinting={printing}
                            onCreate={() => setShowCreateDialog(true)}
                            onPrinted={handlePrintSelected}
                            onEdit={handleEditNepredate}
                            onPrint={handlePrintSelected}
                            onDelete={handleDeleteNepredate}
                        />
                    </TabsContent>

                    {/* AWB-uri predate */}
                    <TabsContent value="predate" className="space-y-4">
                        <AwbPredate />
                    </TabsContent>

                    {/* AWB-uri de retur */}
                    <TabsContent value="retururi" className="space-y-4">
                        <AwbRetururi />
                    </TabsContent>
                </Tabs>
            </div>

            {/* Edit AWB Dialog */}
            {loadingAwb && showEditDialog && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white p-6 rounded-lg">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p className="mt-4">Se incarca...</p>
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
                    header="Awb nou"
                    modal={true}
                    maximized={true}
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
            
            {/* Toast */}
            <Toast ref={toast} />
            
            {/* Print Individual AWB Options Dialog */}
            {AwbPrintDialog}
            
            {/* Delete Confirmation Dialog */}
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
