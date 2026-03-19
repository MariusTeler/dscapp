import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button as UIButton } from '@/components/ui/button';
import { printAwbsPdf, printMastersPdf, printPuisoriPdf } from '@/services/awbs';
import type { ToastMessageState } from '@/components/ui/primereact/toast';
import type { UserPrefs } from '@/types';
import { useCallback, useRef, useState } from 'react';

interface UseAwbPrintOptions {
    prefs: unknown;
    userId: number;
    onToast: (message: ToastMessageState) => void;
    onPrinted: (rowIds: number[], printedBy: number) => void;
}

export function useAwbPrint({ prefs, userId, onToast, onPrinted }: UseAwbPrintOptions) {
    const onToastRef = useRef(onToast);
    const onPrintedRef = useRef(onPrinted);
    onToastRef.current = onToast;
    onPrintedRef.current = onPrinted;

    const [selectedAwbsForPrint, setSelectedAwbsForPrint] = useState<number[]>([]);
    const [showAwbPrintDialog, setShowAwbPrintDialog] = useState(false);
    const [awbPrintDialogType, setAwbPrintDialogType] = useState<'both' | 'separate'>('both');
    const [printing, setPrinting] = useState(false);

    const handlePrintAwbMaster = useCallback(async () => {
        if (selectedAwbsForPrint.length === 0) return;

        setShowAwbPrintDialog(false);
        setPrinting(true);
        try {
            await printMastersPdf(selectedAwbsForPrint);
            onToastRef.current({ severity: 'success', summary: 'Succes', detail: 'Master AWB PDF generat cu succes!' });
            onPrintedRef.current(selectedAwbsForPrint, userId);
        } catch (error) {
            console.error('Error printing Master AWB PDF:', error);
            onToastRef.current({ severity: 'error', summary: 'Eroare', detail: 'Eroare la generarea PDF-ului Master AWB.' });
        } finally {
            setSelectedAwbsForPrint([]);
            setPrinting(false);
        }
    }, [selectedAwbsForPrint, userId]);

    const handlePrintAwbPuisori = useCallback(async () => {
        if (selectedAwbsForPrint.length === 0) return;

        setShowAwbPrintDialog(false);
        setPrinting(true);
        try {
            await printPuisoriPdf(selectedAwbsForPrint);
            onToastRef.current({ severity: 'success', summary: 'Succes', detail: 'Puisori AWB PDF generat cu succes!' });
            onPrintedRef.current(selectedAwbsForPrint, userId);
        } catch (error) {
            console.error('Error printing Puisori AWB PDF:', error);
            onToastRef.current({ severity: 'error', summary: 'Eroare', detail: 'Eroare la generarea PDF-ului Puisori AWB.' });
        } finally {
            setSelectedAwbsForPrint([]);
            setPrinting(false);
        }
    }, [selectedAwbsForPrint, userId]);

    const handlePrintAwbMasterAndPuisori = useCallback(async () => {
        if (selectedAwbsForPrint.length === 0) return;

        setShowAwbPrintDialog(false);
        setPrinting(true);
        try {
            await printAwbsPdf(selectedAwbsForPrint);
            onToastRef.current({ severity: 'success', summary: 'Succes', detail: 'AWB PDF generat cu succes!' });
            onPrintedRef.current(selectedAwbsForPrint, userId);
        } catch (error) {
            console.error('Error printing AWB PDF:', error);
            onToastRef.current({ severity: 'error', summary: 'Eroare', detail: 'Eroare la generarea PDF-ului AWB.' });
        } finally {
            setSelectedAwbsForPrint([]);
            setPrinting(false);
        }
    }, [selectedAwbsForPrint, userId]);

    const handlePrintSelected = useCallback(async (rowIds: number[]) => {
        const printAwbType = (prefs as UserPrefs)?.print || 1;

        if (rowIds.length === 0) {
            onToastRef.current({ severity: 'warn', summary: 'Atentie', detail: 'Va rugam selectati cel putin un AWB pentru printare.' });
            return;
        }
        if (rowIds.length === 1 && (rowIds[0] === undefined || rowIds[0] === null || isNaN(rowIds[0]) || rowIds[0] <= 0)) {
            onToastRef.current({ severity: 'error', summary: 'Eroare', detail: 'AWB invalid.' });
            return;
        }
        if (rowIds.length > 300) {
            onToastRef.current({ severity: 'warn', summary: 'Atentie', detail: 'Maximum 300 AWB-uri pot fi selectate pentru printare.' });
            return;
        }

        console.log('Printing AWB IDs:', rowIds);

        if (printAwbType === 3 || printAwbType === 4) {
            setSelectedAwbsForPrint(rowIds);
            setAwbPrintDialogType('separate');
            setShowAwbPrintDialog(true);
            return;
        }

        if (printAwbType === 10) {
            setSelectedAwbsForPrint(rowIds);
            setAwbPrintDialogType('both');
            setShowAwbPrintDialog(true);
            return;
        }

        try {
            setPrinting(true);
            await printAwbsPdf(rowIds);
            onToastRef.current({ severity: 'success', summary: 'Succes', detail: 'AWB PDF generat cu succes!' });
            onPrintedRef.current(rowIds, userId);
        } catch (error) {
            console.error('Error printing AWB PDFs:', error);
            onToastRef.current({ severity: 'error', summary: 'Eroare', detail: 'Eroare la generarea PDF-urilor AWB.' });
        } finally {
            setPrinting(false);
        }
    }, [prefs, userId]);

    const AwbPrintDialog = (
        <Dialog open={showAwbPrintDialog} onOpenChange={setShowAwbPrintDialog}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Selectați opțiunea de printare AWB</DialogTitle>
                    <DialogDescription>
                        Alegeți ce tip de AWB doriți să printați.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter className="flex gap-2">
                    {awbPrintDialogType === 'both' ? (
                        <>
                            <UIButton onClick={handlePrintAwbMaster} variant="default">Print Master</UIButton>
                            <UIButton onClick={handlePrintAwbMasterAndPuisori} variant="default">Print Master si Puisori</UIButton>
                            <UIButton onClick={() => setShowAwbPrintDialog(false)} variant="outline">Anulează</UIButton>
                        </>
                    ) : (
                        <>
                            <UIButton onClick={handlePrintAwbMaster} variant="default">Print Master</UIButton>
                            <UIButton onClick={handlePrintAwbPuisori} variant="default">Print Puisori</UIButton>
                            <UIButton onClick={() => setShowAwbPrintDialog(false)} variant="outline">Anulează</UIButton>
                        </>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );

    return {
        printing,
        handlePrintSelected,
        AwbPrintDialog,
    };
}
