import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { SharedData, BreadcrumbItem, AwbData, BorderouData } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { Plus, List } from 'lucide-react';
import { useState, useCallback, useRef, useEffect } from 'react';
import { createBorderou, printBorderouPdf, printBoAwbsPdf, printBoMasterPdf, printBoPuisoriPdf, exportBorderouCsv } from '@/services/borderouri/borderouri-service';
import { data as borderouri } from '@/routes/slips';
import { AwbNepredate } from '@/pages/shipments/awb-nepredate';
import { BorderouList } from './borderou';
import Toast, { ToastRef, showError, showSuccess, showWarn, type ToastMessageState } from '@/components/ui/primereact/toast';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Dialog as ReactDialog } from '@/components/ui/primereact/dialog';
import { Button as UIButton } from '@/components/ui/button';
import AwbForm from '@/pages/awb/awb-form';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { EditAwbDialog } from '../shipments/edit-awb-dialog';
import { createAwb, deleteAwb, getById, updateAwb } from '@/services/awbs';
import { useAwbPrint } from '@/hooks/use-awb-print';

export default function BorderouriIndex() {
    const { auth, pcs, prefs } = usePage<SharedData>().props;
    // Toast ref
    const toast = useRef<ToastRef>(null);

    // Tab state
    const [activeTab, setActiveTab] = useState<string>("nou");

    // Creating borderou state
    const [creating, setCreating] = useState(false);
    
    // Edit dialog state
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [showEditDialog, setShowEditDialog] = useState(false);
    const [editingAwb, setEditingAwb] = useState<AwbData | null>(null);
    const [loadingAwb, setLoadingAwb] = useState(false);
    
    // Refresh trigger for AWB list
    const [createUpdateDeleteRow, setCreateUpdateDeleteRow] = useState({ rowId: undefined, newAwbData: null, action: null } as { rowId: number | undefined, newAwbData: AwbData | null, action: 'create' | 'update' | 'delete' | 'bo' | null });
    const [printRows, setPrintRows] = useState({ rowIds: [], printed_by: null } as { rowIds: number[]; printed_by: number | null });
     
    // Print AWB dialog state
    const [showBorderouPrintDialog, setShowBorderouPrintDialog] = useState(false);
    const [borderouPrintDialogType, setBorderouPrintDialogType] = useState<'both' | 'separate'>('both');
    const [selectedBorderouForPrint, setSelectedBorderouForPrint] = useState<BorderouData | null>(null);

    // Toast state
    const [showToast, setShowToast] = useState<ToastMessageState | null>(null);
    
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
            else if(showToast.severity === 'warn')
                showWarn(toast, showToast.summary, showToast.detail || 'Atentie!');
        }
    }, [showToast]);    
    
    // Print borderou as PDF
    const handlePrintBorderou = useCallback(async (borderou: BorderouData) => {
        try {
            await printBorderouPdf(borderou.id);
            setShowToast({
                severity: 'success',
                summary: 'Succes',
                detail: 'Borderou PDF generat cu succes!'
            });
        } catch (error) {
            console.error('Error printing borderou PDF:', error);
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: 'Eroare la generarea PDF-ului borderoului.'
            });
        }
    }, []);

    // Print AWBs from borderou as PDF
    const handlePrintBorderouAwbs = useCallback(async (borderou: BorderouData) => {
        const printAwb = borderou.print_awb || 1;
        
        // If print_awb is 1, 2, or 7: call print directly
        if (printAwb === 0 || printAwb === 1 || printAwb === 2 || printAwb === 5 || printAwb === 6 || printAwb === 7 || printAwb === 8) {
            try {
                await printBoAwbsPdf(borderou.id);
                setShowToast({
                    severity: 'success',
                    summary: 'Succes',
                    detail: 'PDF generat cu succes!'
                });
            } catch (error) {
                console.error('Error printing AWBs PDF:', error);
                setShowToast({
                    severity: 'error',
                    summary: 'Eroare',
                    detail: 'Eroare la generarea PDF-ului.'
                });
            }
            return;
        }
        
        // If print_awb is 3 or 4: show dialog with Master/Puisori options
        if (printAwb === 3 || printAwb === 4 || printAwb === 10) {
            setSelectedBorderouForPrint(borderou);
            setBorderouPrintDialogType('separate');
            setShowBorderouPrintDialog(true);
            return;
        }
        
        // Otherwise: show dialog with Master/Master+Puisori options
        setSelectedBorderouForPrint(borderou);
        setBorderouPrintDialogType('both');
        setShowBorderouPrintDialog(true);
    }, []);
    
    // Handle print dialog actions
    const handlePrintBorderouMaster = useCallback(async () => {
        if (!selectedBorderouForPrint) return;
        
        setShowBorderouPrintDialog(false);
        try {
            await printBoMasterPdf(selectedBorderouForPrint.id);
            setShowToast({
                severity: 'success',
                summary: 'Succes',
                detail: 'PDF generat cu succes!'
            });
        } catch (error) {
            console.error('Error printing Master AWBs PDF:', error);
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: 'Eroare la generarea PDF-ului.'
            });
        } finally {
            setSelectedBorderouForPrint(null);
        }
    }, [selectedBorderouForPrint]);
    
    const handlePrintBorderouPuisori = useCallback(async () => {
        if (!selectedBorderouForPrint) return;
        
        setShowBorderouPrintDialog(false);
        try {
            await printBoPuisoriPdf(selectedBorderouForPrint.id);
            setShowToast({
                severity: 'success',
                summary: 'Succes',
                detail: 'PDF generat cu succes!'
            });
        } catch (error) {
            console.error('Error printing Puisori AWBs PDF:', error);
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: 'Eroare la generarea PDF-ului.'
            });
        } finally {
            setSelectedBorderouForPrint(null);
        }
    }, [selectedBorderouForPrint]);
    
    const handlePrintBorderouMasterAndPuisori = useCallback(async () => {
        if (!selectedBorderouForPrint) return;
        
        setShowBorderouPrintDialog(false);
        try {
            // Print both sequentially
            await printBoAwbsPdf(selectedBorderouForPrint.id);
            setShowToast({
                severity: 'success',
                summary: 'Succes',
                detail: 'PDF generat cu succes!'
            });
        } catch (error) {
            console.error('Error printing Master and Puisori AWBs PDF:', error);
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: 'Eroare la generarea PDF-ului.'
            });
        } finally {
            setSelectedBorderouForPrint(null);
        }
    }, [selectedBorderouForPrint]);

    // Export borderou data as CSV
    const handleExportBorderouCsv = useCallback(async (borderou: BorderouData) => {
        try {
            await exportBorderouCsv(borderou.id);
            setShowToast({
                severity: 'success',
                summary: 'Succes',
                detail: 'Fisier CSV exportat cu succes!'
            });
        } catch (error) {
            console.error('Error exporting CSV:', error);
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: 'Eroare la exportul CSV.'
            });
        }
    }, []);

    // Create new borderou from selected AWBs
    const handleCreateBorderou = async (awbIds: number[]) => {
        if (awbIds.length === 0) {
            setShowToast({
                severity: 'warn',
                summary: 'Atentie',
                detail: 'Va rugam selectati cel putin un AWB pentru a crea un borderou.'
            });
            return;
        }

        console.log('Creating borderou with AWB IDs:', awbIds);
        
        setCreating(true);
        try {
            const response = await createBorderou(awbIds);
            
            if (response.success) {
                setShowToast({
                    severity: 'success',
                    summary: 'Succes',
                    detail: `Borderou creat cu succes! ID: ${response.data?.borderou_id || 'N/A'}`
                });
                // Stay on nou tab and refresh AWB list
                setCreateUpdateDeleteRow({ rowId: undefined, newAwbData: null, action: 'bo' });
            } else {
                setShowToast({
                    severity: 'error',
                    summary: 'Eroare',
                    detail: response.message || 'Eroare la crearea borderoului.'
                });
            }
        } catch (error) {
            console.error('Error creating borderou:', error);
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: error instanceof Error ? error.message : 'Eroare la crearea borderoului.'
            });
        } finally {
            setCreating(false);
        }
    };

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

    // Handle row actions
    const handleEditAwb = useCallback(async (awb: AwbData) => {
        if(awb.can_update === false) {
            setShowToast({
                severity: 'error',
                summary: 'Eroare',
                detail: 'Nu se poate edita un AWB care a fost deja printat.'
            });
            return;
        }
        setLoadingAwb(true);
        setShowEditDialog(true); // Show dialog with loading state
        
        const result = await getById(awb.id ?? 0);
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

    const handleUpdateAwb = useCallback(async (formData: AwbData) => {
        if (!editingAwb) return;
        const result = await updateAwb(editingAwb.id, formData);
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
                detail: result.message || 'Eroare la actualizarea AWB.'
            });
        }
    }, [editingAwb]);

    const handleDeleteAwb = useCallback(async (awb: AwbData) => {
        setAwbToDelete(awb);
        setShowDeleteConfirm(true);
    }, []);
    
    const handleConfirmDelete = useCallback(async () => {
        if (!awbToDelete) return;

        const result = await deleteAwb(awbToDelete.id ?? 0);
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

    const { printing, handlePrintSelected, AwbPrintDialog } = useAwbPrint({
        prefs,
        userId: auth.user.id,
        onToast: setShowToast,
        onPrinted: (rowIds, printedBy) => setPrintRows({ rowIds, printed_by: printedBy }),
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Borderouri', href: borderouri.url() }
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Borderouri" />
            <Toast ref={toast} />

            <div className="space-y-2">
                <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="nou" className="flex items-center gap-2">
                            <Plus className="h-4 w-4" />
                            Borderou nou
                        </TabsTrigger>
                        <TabsTrigger value="lista" className="flex items-center gap-2">
                            <List className="h-4 w-4" />
                            Lista
                        </TabsTrigger>
                    </TabsList>

                    {/* First Tab: Create New Borderou */}
                    <TabsContent value="nou" className="space-y-4">
                        <AwbNepredate
                            mode="borderouri"
                            isCreating={creating}
                            isPrinting={printing}
                            createUpdateDeleteRow={createUpdateDeleteRow}
                            printRows={printRows}
                            onSelected={handleCreateBorderou}
                            onPrinted={handlePrintSelected}
                            onCreate={() => setShowCreateDialog(true)}
                            onEdit={handleEditAwb}
                            onPrint={handlePrintSelected}
                            onDelete={handleDeleteAwb}
                        />
                    </TabsContent>

                    {/* Second Tab: List Existing Borderouri */}
                    <TabsContent value="lista" className="space-y-4">
                        <BorderouList 
                            onPrintBorderou={handlePrintBorderou}
                            onPrintAwbs={handlePrintBorderouAwbs}
                            onPrint={handlePrintSelected}
                            onExportCsv={handleExportBorderouCsv}
                        />
                    </TabsContent>
                </Tabs>

                {/* Print AWB Options Dialog */}
                <Dialog open={showBorderouPrintDialog} onOpenChange={setShowBorderouPrintDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Selectați opțiunea de printare</DialogTitle>
                            <DialogDescription>
                                Alegeți ce tip de AWB-uri doriți să printați.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter className="flex gap-2">
                            {borderouPrintDialogType === 'both' ? (
                                <>
                                    <UIButton onClick={handlePrintBorderouMaster} variant="default">
                                        Print Master
                                    </UIButton>
                                    <UIButton onClick={handlePrintBorderouMasterAndPuisori} variant="default">
                                        Print Master si Puisori
                                    </UIButton>
                                    <UIButton onClick={() => setShowBorderouPrintDialog(false)} variant="outline">
                                        Anulează
                                    </UIButton>
                                </>
                            ) : (
                                <>
                                    <UIButton onClick={handlePrintBorderouMaster} variant="default">
                                        Print Master
                                    </UIButton>
                                    <UIButton onClick={handlePrintBorderouPuisori} variant="default">
                                        Print Puisori
                                    </UIButton>
                                    <UIButton onClick={() => setShowBorderouPrintDialog(false)} variant="outline">
                                        Anulează
                                    </UIButton>
                                </>
                            )}
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Print Individual AWB Options Dialog */}
                {AwbPrintDialog}

                {showCreateDialog && (
                    <ReactDialog
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
                    </ReactDialog>
                )}

                {/* Edit AWB Dialog */}
                {loadingAwb && showEditDialog && (
                    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                        <div className="bg-white p-6 rounded-lg">
                            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                            <p className="mt-4">Se incarca...</p>
                        </div>
                    </div>
                )}
    
                {showEditDialog && !loadingAwb && editingAwb && (
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
            </div>
        </AppLayout>
    );
}
