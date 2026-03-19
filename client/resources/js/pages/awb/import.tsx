import { Card, CardContent } from '@/components/ui/primereact/card';
import { Button } from '@/components/ui/button';
import { Stepper, StepperPanel } from '@/components/ui/primereact/stepper';
import { Stepper as PrimeStepper } from 'primereact/stepper';
import { Upload, Download, FileText, ListTodo, ClipboardList, ImportIcon } from 'lucide-react';
import { useState, useRef, useCallback } from 'react';
import { validateAwbImport, importAwbData } from '@/services/imports';
import { templateCsv, templateXls } from '@/routes/awb/import';
import Toast, { ToastRef, showError } from '@/components/ui/primereact/toast';
import { type ImportValidationResponse, SharedData, UserPrefs } from '@/types';
import { router } from '@inertiajs/react';
import { index as shipmentsIndex } from '@/routes/shipments';
import { index as borderouriIndex } from '@/routes/slips';

interface ImportProps {
    prefs?: SharedData['prefs'];
    onComplete?: () => void;
}

export function Import({ prefs, onComplete }: ImportProps) {
    const importcsv: boolean = (prefs as UserPrefs)?.importcsv || false;
    const importxls: boolean = importcsv &&(prefs as UserPrefs)?.importxls || false;
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [step, setStep] = useState<'select' | 'validate' | 'complete'>('select');
    const [, setProgress] = useState(0);
    const [loading, setLoading] = useState(false);
    const [validationData, setValidationData] = useState<ImportValidationResponse['data'] | null>(null);
    const [validationError, setValidationError] = useState<string | null>(null);
    const [importDataError, setImportDataError] = useState<ImportValidationResponse['data'] | null>(null);
    const [importError, setImportError] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const stepperRef = useRef<PrimeStepper>(null);
    const toast = useRef<ToastRef>(null);

    const handleFileSelect = useCallback(async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (file && (file.type === 'text/csv' || file.type === 'application/vnd.ms-excel' || file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
            setSelectedFile(file);
            setLoading(true);
            setValidationError(null);

            // Validate file on server
            const result = await validateAwbImport(file);

            setLoading(false);

            if (result.success && result.data) {
                setValidationData(result.data);
                setStep('validate');
            } else {
                setValidationError(result.message);
                showError(toast, 'Eroare validare', result.message);
            }
        } else {
            setValidationError('Va rugam selectati un fisier valid.');
            showError(toast, 'Fisier invalid', 'Va rugam selectati un fisier valid.');
        }
    }, []);

    const handleChooseFile = useCallback(() => {
        fileInputRef.current?.click();
    }, []);


    const handleImportComplete = useCallback(async () => {
        if (!validationData || !validationData.import_id || validationData.total_valid === 0) return;

        setLoading(true);
        setImportError(null);

        // Import the validated data
        const result = await importAwbData(validationData.import_id);

        setLoading(false);

        if (result.success) {
            setStep('complete');
            setProgress(100);
            if(result.data && result.data.errors && result.data.errors.length > 0) {
                setImportDataError(result.data);
                showError(toast, 'Import finalizat cu erori', `Importul a fost finalizat, dar au fost intampinate erori la procesarea unor linii. Verificati raportul de erori pentru detalii.`);
            } else {
                toast.current?.show({ severity: 'success', summary: 'Import finalizat', detail: 'Toate expeditiile au fost importate cu succes.', life: 1000 });
            }
            onComplete?.();
        } else {
            setImportError(result.message);
            showError(toast, 'Eroare import', result.message);
        }
    }, [validationData, onComplete, setProgress]);

    const handleImportReset = useCallback(() => {
        setSelectedFile(null);
        setStep('select');
        setProgress(0);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
        setValidationData(null);
        setImportDataError(null);
        setValidationError(null);
        setImportError(null);
    }, [setSelectedFile, setProgress]);

    return (
        <>
            <Toast ref={toast} />
            <Card>
                <CardContent className="space-y-4">
                    <Stepper ref={stepperRef} activeStep={step === 'select' ? 0 : step === 'validate' ? 1 : 2} linear>
                    {/* Step 1: File Selection */}
                    <StepperPanel header="Fisier CSV">
                        <Card>
                            <CardContent className="space-y-6">
                                <div className="flex flex-col items-center justify-center py-8 space-y-4">
                                    <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center">
                                        <FileText className="h-12 w-12 text-gray-400" />
                                    </div>
                                    <div className="text-center">
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Selectează fișierul {importxls ? 'XLS' : importcsv ? 'CSV' : ''} pentru import
                                        </h3>
                                        <p className="text-sm text-gray-500 mt-1">
                                            Încarcă un fișier {importxls ? 'XLS' : importcsv ? 'CSV' : ''} cu coloanele din template
                                        </p>
                                    </div>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept= {importxls ? ".xlsx, .xls" : importcsv ? ".csv" : undefined}
                                        onChange={handleFileSelect}
                                        className="hidden"
                                    />
                                    <div className="flex gap-4">
                                        <Button
                                            onClick={handleChooseFile}
                                            className="flex items-center gap-2"
                                        >
                                            <Upload className="h-4 w-4" />
                                            Alege fisier
                                        </Button>
                                        <Button
                                            variant="outline"
                                            className="flex items-center gap-2"
                                            asChild
                                        >
                                            <a
                                                href={importxls ? templateXls.url() : importcsv ? templateCsv.url() : undefined}
                                                download={`template_import_awb_${new Date().toISOString().split('T')[0]}.${importxls ? 'xls' : importcsv ? 'csv' : ''}`}
                                            >
                                                <Download className="h-4 w-4" />
                                                Descarca template
                                            </a>
                                        </Button>
                                    </div>
                                </div>
                                <h4 className="font-medium text-red-600 mb-2">Toate coloanele sunt obligatorii</h4>
                                <h4 className="font-medium text-red-600 mb-2">Respectati ordinea coloanelor din template</h4>
                                <div className="flex flex-row gap-6">
                                    
                                    <div className="border-t pt-4 flex-1">
                                        <h4 className="font-medium text-red-600 mb-2">Valori obligatorii</h4>
                                        <div className="flex gap-8">
                                            <ul className="text-sm text-gray-600 space-y-1 list-disc list-inside">
                                                <li><span className="font-bold text-red-600">destinatar</span> : Nume destinatar</li>
                                                <li><span className="font-bold text-red-600">judet</span> : Judet destinatar</li>
                                                <li><span className="font-bold text-red-600">localitate</span> : Localitate destinatar</li>
                                                <li><span className="font-bold text-red-600">adresa</span> : Adresa destinatar</li>
                                                <li><span className="font-bold text-red-600">tip</span> : Tip (plic, colet, palet)</li>
                                                <li><span className="font-bold text-red-600">piese</span> : Nr. piese (obligatoriu la colet) : implicit 1 la plic sau palet</li>
                                                <li><span className="font-bold text-red-600">greutate</span> : Greutate (kg) (obligatoriu la colet si palet) : implicit 0.5 la plic</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div className="border-t pt-4 flex-1">
                                        <h4 className="font-medium text-blue-600 mb-2">Valori optionale</h4>
                                        <ul className="text-sm text-gray-600 space-y-1 list-disc list-inside">
                                            <li><span className="font-bold text-blue-600">platitor</span> : Platitor (expeditor, destinatar) : implicit expeditor</li>
                                            <li><span className="font-bold text-blue-600">contact</span> : Contact destinatar</li>
                                            <li><span className="font-bold text-blue-600">telefon</span> : Telefon destinatar (obligatoriu daca sms_livrare)</li>
                                            <li><span className="font-bold text-blue-600">email</span> : Email destinatar</li>
                                            <li><span className="font-bold text-blue-600">valoare_declarata</span> : Valoare declarata</li>
                                            <li><span className="font-bold text-blue-600">ramburs</span> : Ramburs</li>
                                            <li><span className="font-bold text-blue-600">tip_plata</span> : Tip plata (cash, cont, bo, cec) : implicit cash</li>
                                            <li><span className="font-bold text-blue-600">retur_nt</span> : Retur NT (da, nu) : implicit nu</li>
                                            <li><span className="font-bold text-blue-600">retur_documente</span> : Retur documente (da, nu) : implicit nu</li>
                                            <li><span className="font-bold text-blue-600">retur_ambalaj</span> : Retur ambalaj (da, nu) : implicit nu</li>
                                            <li><span className="font-bold text-blue-600">retur_colet</span> : Retur colet (da, nu) : implicit nu</li>
                                            <li><span className="font-bold text-blue-600">sms_livrare</span> : SMS livrare (da, nu) : implicit nu</li>
                                            <li><span className="font-bold text-blue-600">deschidere_colet</span> : Deschidere colet (da, nu) : implicit nu</li>
                                            <li><span className="font-bold text-blue-600">livrare_sambata</span> : Livrare sambata (da, nu) : implicit nu</li>
                                            <li><span className="font-bold text-blue-600">livrare_sediu</span> : Livrare sediu (da, nu) : implicit nu</li>
                                            <li><span className="font-bold text-blue-600">observatii</span> : Observatii</li>
                                            <li><span className="font-bold text-blue-600">detalii_documente</span> : Detalii documente</li>
                                        </ul>
                                    </div>
                                </div>
                                <div className="flex justify-end pt-4">
                                    <Button
                                        onClick={() => {
                                            if (stepperRef.current?.nextCallback) {
                                                stepperRef.current.nextCallback();
                                                setStep('validate');
                                            }
                                        }}
                                        disabled={!selectedFile}
                                    >
                                        Urmator
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </StepperPanel>

                    {/* Step 2: Validation */}
                    <StepperPanel header="Validare">
                        {selectedFile && (
                            <Card>
                                <CardContent className="space-y-6">
                                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center justify-between">
                                        <div className="space-y-1">
                                            <p className="font-medium text-blue-900">Fisier selectat:</p>
                                            <p className="text-blue-700">{selectedFile.name}</p>
                                            <p className="text-sm text-blue-600">{(selectedFile.size / 1024).toFixed(2)} KB</p>
                                        </div>
                                        <Button
                                            onClick={handleImportReset}
                                            variant="ghost"
                                            size="sm"
                                        >
                                            Schimba fisier
                                        </Button>
                                    </div>
                                    {validationData && (
                                        <div className="grid grid-cols-3 gap-4">
                                            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                                <h4 className="font-medium text-green-900 mb-1">Linii valide</h4>
                                                <p className="text-2xl font-bold text-green-700">{validationData.total_valid}</p>
                                            </div>
                                            <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                                <h4 className="font-medium text-red-900 mb-1">Linii eronate</h4>
                                                <p className="text-2xl font-bold text-red-700">{validationData.total_errors}</p>
                                            </div>
                                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                                <h4 className="font-medium text-blue-900 mb-1">Total linii</h4>
                                                <p className="text-2xl font-bold text-blue-700">{validationData.total_rows}</p>
                                            </div>
                                        </div>
                                    )}
                                    <div>
                                        
                                        <div className="border rounded-lg overflow-auto">
                                            {validationData ? (
                                                <>
                                                    <h4 className="font-medium text-gray-900 mb-3">Linii valide</h4>
                                                    <table className="min-w-full table-auto">
                                                        <thead className="bg-gray-50">
                                                            <tr>
                                                                {validationData.columns?.map((col, index) => (
                                                                    <th
                                                                        key={index} 
                                                                        className="px-4 py-2 text-left text-sm font-medium text-gray-500"
                                                                    >
                                                                        {col}
                                                                    </th>
                                                                ))}
                                                            </tr>
                                                        </thead>
                                                        <tbody className="divide-y divide-gray-200">
                                                            {validationData.valid_rows?.map((row, rowIndex) => (
                                                                <tr key={rowIndex} className={rowIndex % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                                                                    {row.map((cell, cellIndex) => (
                                                                        <td
                                                                            key={cellIndex}
                                                                            className="px-4 py-2 text-sm text-gray-700"
                                                                        >
                                                                            {typeof cell === 'string' && cell.length > 50 ? cell.substring(0, 50) + '...' : cell}
                                                                        </td>
                                                                    ))}
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </>
                                            ) : validationError ? (
                                                <p className="text-red-600">{validationError}</p>
                                            ) : (
                                                <p className="text-gray-500">Se incarca previzualizarea...</p>
                                            )}
                                        </div>
                                    </div>
                                    {validationData && validationData.errors && validationData.errors.length > 0 && (
                                        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                            <h4 className="font-medium text-red-900 mb-3">Erori de validare</h4>
                                            <div className="space-y-2 max-h-60 overflow-y-auto">
                                                {validationData.errors.map((error, index) => (
                                                    <div key={index} className="bg-white p-3 rounded border border-red-300">
                                                        <p className="text-sm font-semibold text-red-800 mb-1">
                                                            Linia {error.row}:
                                                        </p>
                                                        <ul className="list-disc list-inside text-sm text-red-700 space-y-1">
                                                            {error.errors.map((errMsg, errIndex) => (
                                                                <li key={errIndex}>{errMsg}</li>
                                                            ))}
                                                        </ul>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <Button
                                            onClick={() => {
                                                if (stepperRef.current?.prevCallback) {
                                                    stepperRef.current.prevCallback();
                                                    setStep('select');
                                                }
                                            }}
                                            variant="outline"
                                        >
                                            Inapoi
                                        </Button>
                                        <Button
                                            onClick={() => {
                                                handleImportComplete();
                                                if (stepperRef.current?.nextCallback) {
                                                    stepperRef.current.nextCallback();
                                                }
                                            }}
                                            disabled={loading || !validationData || !validationData?.import_id || validationData.total_valid === 0}
                                        >
                                            {loading ? 'Se importa...' : 'Importa'}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </StepperPanel>

                    {/* Step 3: Import Complete */}
                    <StepperPanel header="Finalizare import">
                        <Card>
                            <CardContent className="text-center py-12">
                                <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                {importError ? (
                                    <>
                                        <h3 className="text-lg font-medium text-red-900 mb-2">EROARE!</h3>
                                        <p className="text-red-500 mb-6">{importError}.</p>
                                    </>
                                ) : importDataError && importDataError.errors && importDataError.errors.length > 0 ? (
                                    <>
                                        <h3 className="text-lg font-medium text-red-900 mb-2">Import finalizat cu erori!</h3>
                                        <p className="text-gray-500 mb-6">Importul a fost finalizat, dar au fost intampinate erori la procesarea unor linii. Verificati raportul de erori pentru detalii.</p>
                                    </>
                                ) : (
                                    <>
                                        <h3 className="text-lg font-medium text-gray-900 mb-2">Import finalizat cu succes!</h3>
                                        <p className="text-gray-500 mb-6">Toate expeditiile au fost importate in sistem fara erori.</p>
                                    </>
                                )}
                                <div className="flex justify-center gap-4">
                                    {!importError && (
                                        <>
                                        <Button
                                            onClick={() => router.visit(borderouriIndex().url)}
                                            className="flex items-center gap-2"
                                        >
                                            <ListTodo className="size-4 shrink-0" />
                                            <span className="font-medium">Borderou nou</span>
                                        </Button>
                                        <Button
                                            onClick={() => router.visit(shipmentsIndex().url)}
                                            className="flex items-center gap-2"
                                        >
                                            <ClipboardList className="size-4 shrink-0" />
                                            <span className="font-medium">Lista nepredate</span>
                                        </Button>
                                    </>
                                    )}
                                    <Button
                                        onClick={handleImportReset}
                                        variant="outline"
                                        className="flex items-center gap-2"
                                        >
                                            <ImportIcon className="size-4 shrink-0" />
                                            <span className="font-medium">Import nou</span>
                                    </Button>
                                </div>
                                {importDataError && importDataError.errors && importDataError.errors.length > 0 && (
                                    <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <h4 className="font-medium text-red-900 mb-3">Erori de validare</h4>
                                        <div className="space-y-2 max-h-60 overflow-y-auto">
                                            {importDataError.errors.map((error, index) => (
                                                <div key={index} className="bg-white p-3 rounded border border-red-300">
                                                    <p className="text-sm font-semibold text-red-800 mb-1">
                                                        Linia {error.row}:
                                                    </p>
                                                    <ul className="list-disc list-inside text-sm text-red-700 space-y-1">
                                                        {error.errors.map((errMsg, errIndex) => (
                                                            <li key={errIndex}>{errMsg}</li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </StepperPanel>
                </Stepper>
            </CardContent>
        </Card>
        </>
    );
}
