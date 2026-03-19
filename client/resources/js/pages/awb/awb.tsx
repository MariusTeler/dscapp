import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import * as awbRoutes from '@/routes/awb';
import { index as shipmentsIndex } from '@/routes/shipments';
import { index as borderouriIndex } from '@/routes/slips';
import { type SharedData, type BreadcrumbItem, type AwbData, UserPrefs} from '@/types';
import { Head, router } from '@inertiajs/react';
import { CirclePlus, ClipboardList, ListTodo, Printer, ImportIcon, ArrowLeftToLineIcon, ArrowRightFromLine} from 'lucide-react';
import { useState, useCallback, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import Toast, { ToastRef, showError, showSuccess, showWarn } from '@/components/ui/primereact/toast';
import AwbForm from '@/pages/awb/awb-form';
import AwbInversForm from '@/pages/awb/awb-invers-form';
import { Import } from '@/pages/awb/import';
import { createAwb } from '@/services/awbs';
import { useAwbPrint } from '@/hooks/use-awb-print';

export default function AwbIndex() {
    const { auth, pcs, prefs } = usePage<SharedData>().props;
    const importcsv: boolean = (prefs as UserPrefs)?.importcsv || false;
    const importxls: boolean = importcsv &&(prefs as UserPrefs)?.importxls || false;
    const [activeTab, setActiveTab] = useState('create');
    const [awbCreated, setAwbCreated] = useState<number | null>(null);
    const toast = useRef<ToastRef>(null);

    const { handlePrintSelected, AwbPrintDialog } = useAwbPrint({
        prefs,
        userId: auth.user.id,
        onToast: (msg) => {
            if (msg.severity === 'success') showSuccess(toast, msg.summary, msg.detail || '');
            else if (msg.severity === 'error') showError(toast, msg.summary, msg.detail || '');
            else if (msg.severity === 'warn') showWarn(toast, msg.summary, msg.detail || '');
        },
        onPrinted: () => {},
    });

    // Handle autocomplete search errors
    const handleSearchError = useCallback((error: string) => {
        showError(toast, 'Eroare căutare', error);
    }, []);

    // Handle AWB creation
    const handleCreateAwb = useCallback(async (formData: AwbData) => {
            const result = await createAwb(formData);
            if (result.success) {
                setAwbCreated(result.data?.id ?? null);
            } else {
                showError(toast, 'Eroare', result.message || 'Eroare la crearea AWB');
            }
        }, []);

    // Import handlers
    const handleImportComplete = useCallback(() => {
        router.visit(awbRoutes.index().url, { preserveState: true });
    }, []);

    const getBreadcrumbForTab = (tabValue: string): BreadcrumbItem[] => {
        const baseBreadcrumbs: BreadcrumbItem[] = [
            { title: 'AWB', href: awbRoutes.index().url }
        ];

        switch (tabValue) {
            case 'create_swapped':
                return [...baseBreadcrumbs, { title: 'Expeditie inversa', href: awbRoutes.index().url }];
            case 'import':
                return [...baseBreadcrumbs, { title: 'Import', href: awbRoutes.index().url }];
            default:
                return [...baseBreadcrumbs, { title: 'Expeditie noua', href: awbRoutes.index().url }];
        }
    };

    const breadcrumbs: BreadcrumbItem[] = getBreadcrumbForTab(activeTab);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="AWB" />
            <Toast ref={toast} />
            {AwbPrintDialog}

            <div className="space-y-2">

                {awbCreated !== null ? (
                    <div className="flex flex-col items-center gap-6 p-8">
                        <p className="text-lg font-medium text-teal-600">AWB-ul a fost generat cu succes!</p>
                        <div className="grid w-full max-w-lg grid-cols-2 gap-4">
                            <button
                                onClick={() => router.visit(borderouriIndex().url)}
                                className="flex items-center gap-3 rounded-lg border bg-white p-2 text-left text-[#1e3a5f] transition-colors hover:bg-slate-50"
                            >
                                <ListTodo className="size-6 shrink-0" />
                                <span className="font-medium">Borderou nou</span>
                            </button>
                            <button
                                onClick={() => router.visit(shipmentsIndex().url)}
                                className="flex items-center gap-3 rounded-lg border bg-white p-2 text-left text-[#1e3a5f] transition-colors hover:bg-slate-50"
                            >
                                <ClipboardList className="size-6 shrink-0" />
                                <span className="font-medium">Lista nepredate</span>
                            </button>
                            <button
                                onClick={() => handlePrintSelected([awbCreated])}
                                className="flex items-center gap-3 rounded-lg border bg-white p-4 text-left text-[#1e3a5f] transition-colors hover:bg-slate-50"
                            >
                                <Printer className="size-4 shrink-0" />
                                <span className="font-medium">Print</span>
                            </button>
                            <button
                                onClick={() => setAwbCreated(null)}
                                className="flex items-center gap-3 rounded-lg border bg-white p-4 text-left text-[#1e3a5f] transition-colors hover:bg-slate-50"
                            >
                                <CirclePlus className="size-4 shrink-0" />
                                <span className="font-medium">Awb nou</span>
                            </button>
                        </div>
                    </div>
                ) : (
                    /* Tabs Navigation */
                    <Tabs defaultValue="create" onValueChange={setActiveTab}>
                        <TabsList className="grid w-full grid-cols-3">
                            <TabsTrigger value="create" className="flex items-center gap-2">
                                <ArrowRightFromLine className="size-4" />
                                Awb nou
                            </TabsTrigger>
                            <TabsTrigger value="create_swapped" className="flex items-center gap-2">
                                <ArrowLeftToLineIcon className="size-4" />
                                Awb invers
                            </TabsTrigger>
                            <TabsTrigger value="import" className="flex items-center gap-2">
                                <ImportIcon className="size-4" />
                                Import
                            </TabsTrigger>
                        </TabsList>

                        {/* Tab 1: Create AWB Form */}
                        <TabsContent value="create" className="space-y-4">
                            <AwbForm 
                                auth={auth}
                                pcs={pcs}
                                prefs={prefs}
                                onCreate={handleCreateAwb}
                                onSearchError={handleSearchError}
                            />
                        </TabsContent>

                        {/* Tab 2: AWB AWB swapped form */}
                        <TabsContent value="create_swapped" className="space-y-4">
                            <AwbInversForm 
                                auth={auth}
                                pcs={pcs}
                                prefs={prefs}
                                onCreate={handleCreateAwb}
                                onSearchError={handleSearchError}
                            />
                        </TabsContent>

                        {/* Tab 3: Import */}
                        <TabsContent value="import" className="space-y-4">
                            {(importcsv || importxls) ? (
                            <Import
                                prefs={prefs}
                                onComplete={handleImportComplete}
                            />
                            )
                            : (
                                <p className="text-sm text-gray-500 italic">Importul CSV este dezactivat pentru contul dumneavoastra. Va rugam contactati administratorul.</p>
                            )}
                        </TabsContent>
                    </Tabs>
                )}
            </div>
        </AppLayout>
    );
}
