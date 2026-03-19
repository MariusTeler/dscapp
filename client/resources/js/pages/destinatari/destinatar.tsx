import { Dialog } from '@/components/ui/primereact/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/primereact/input';
import { Label } from '@/components/ui/label';
import { emailRegex, romanianMobileRegex, type DestinatarData } from '@/types';
import Autocomplete, { type AutocompleteOption } from '@/components/ui/primereact/autocomplete';
import { useState, useCallback, useEffect, memo, startTransition } from 'react';
import { createDestinatar, updateDestinatar, deleteDestinatar} from '@/services/destinatari';
import { Edit, LucideShieldClose, Trash2 } from 'lucide-react';
import { formatDate } from '@/lib/formatters';

interface CreateDialogProps {
    visible: boolean;
    onVisibleChange: (visible: boolean) => void;
    onSuccess?: () => void;
    onFailed?:(message:string) => void;
}

interface EditDialogProps {
    visible: boolean;
    onVisibleChange: (visible: boolean) => void;
    destinatar: DestinatarData | null;
    onSuccess?: () => void;
    onFailed?:(message:string) => void;
}

interface DeleteDialogProps {
    visible: boolean;
    onVisibleChange: (visible: boolean) => void;
    destinatar: DestinatarData | null;
    onSuccess?: () => void;
    onFailed?:(message:string) => void;
}

interface ViewDialogProps {
    visible: boolean;
    onVisibleChange: (visible: boolean) => void;
    destinatar: DestinatarData | null;
    onEdit?: (destinatar: DestinatarData) => void;
    onDelete?: (destinatar: DestinatarData) => void;
}

// Form validation
const validateForm = (formData: DestinatarData): {[key: string]: string} => {
    const errors: {[key: string]: string} = {};

    if (!formData.localitate_id || formData.localitate_id === 0) {
        errors.localitate = 'Alege localitatea din lista';
    }
    if (!formData.nume.trim()) {
        errors.nume = 'Numele este obligatoriu';
    }
    if (!formData.adresa.trim()) {
        errors.adresa = 'Adresa este obligatorie';
    }

    // Email validation (optional but if provided must be valid)
    if (formData.email && formData.email.trim() !== '' && !emailRegex.test(formData.email)) {
        errors.email = 'Adresa de email nu este valida';
    }

    // Romanian mobile phone validation (optional but if provided must be valid)
    if (formData.telefon && formData.telefon.trim() !== '' && !romanianMobileRegex.test(formData.telefon)) {
        errors.telefon = 'Numarul de telefon invalid';
    }

    return errors;
};

// Create Dialog Component
const CreateDestinatarDialogComponent = ({ visible, onVisibleChange, onSuccess, onFailed }: CreateDialogProps) => {
    console.log("CreateDestinatarDialog rendered");
    const [formData, setFormData] = useState<DestinatarData>({
        localitate_id: 0,
        localitate: '',
        nume: '',
        adresa: '',
        contact: null,
        telefon: null,
        email: null
    });
    
    const [formLoading, setFormLoading] = useState(false);
    const [formErrors, setFormErrors] = useState<{[key: string]: string}>({});
    const [localitateSearch, setLocalitateSearch] = useState('');

    const handleLocalitateSelect = useCallback((option: AutocompleteOption) => {
        setFormData(prev => ({ ...prev, localitate_id: Number(option.value) }));
        setFormErrors(prev => {
            const newErrors = { ...prev };
            delete newErrors.localitate;
            return newErrors;
        });
    }, []);

    const handleLocalitateNoResults = useCallback(() => {
        setFormData(prev => ({ ...prev, localitate_id: 0 }));
    }, []);

    const handleSubmit = async () => {
        const errors = validateForm(formData);
        
        if (Object.keys(errors).length > 0) {
            setFormErrors(errors);
            
            // Focus on first error field
            const errorFieldMap: Record<string, string> = {
                localitate: 'localitate',
                nume: 'nume',
                adresa: 'adresa',
                contact: 'contact',
                telefon: 'telefon',
                email: 'email'
            };
            
            const firstErrorKey = Object.keys(errors)[0];
            const inputId = errorFieldMap[firstErrorKey];
            
            if (inputId) {
                setTimeout(() => {
                    const element = document.getElementById(inputId);
                    if (element) {
                        element.focus();
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
            }
            
            return;
        }

        setFormLoading(true);
        try {
            const response = await createDestinatar(formData);
            
            // Reset form and close dialog in a single batch
            startTransition(() => {
                setFormData({
                    localitate_id: 0,
                    localitate: '',
                    nume: '',
                    adresa: '',
                    contact: null,
                    telefon: null,
                    email: null
                });
                setLocalitateSearch('');
                setFormErrors({});
                onVisibleChange(false);
                setFormLoading(false);
            });
            
            if(response.success)
                onSuccess?.();
            else
                onFailed?.(response.message || 'Eroare creare destinatar');
        } catch (error) {
            console.error('Error creating destinatar:', error);
            startTransition(() => {
                onVisibleChange(false);
                setFormLoading(false);
            });
            onFailed?.(error instanceof Error ? error.message : 'Eroare creare destinatar');
        }
    };
    const footerContent = (
        <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => onVisibleChange(false)} disabled={formLoading}>
                Anuleaza
            </Button>
            <Button onClick={handleSubmit} disabled={formLoading}>
                {formLoading ? 'Se salveaza...' : 'Salveaza'}
            </Button>
        </div>
    );

    return (
        <Dialog 
            visible={visible}
            onHide={() => {if (!visible) return; onVisibleChange(false); }}
            header="Adauga destinatar nou"
            footer={footerContent}
            modal={true}
            style={{ width: '50vw' }} 
            breakpoints={{ '960px': '75vw', '641px': '100vw' }}
        >
            <div className="grid gap-4 py-4">
                {/* Localitate */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="localitate" className="text-left pt-2">Localitate *</Label>
                    <div className="col-span-3">
                        <Autocomplete
                            id="localitate"
                            text={localitateSearch}
                            onChange={setLocalitateSearch}
                            onSelect={handleLocalitateSelect}
                            onOpen={handleLocalitateNoResults}
                            onNoResults={handleLocalitateNoResults}
                            searchUrl="/commons/localitati"
                            placeholder="Caută localitate..."
                            minSearchLength={3}
                            invalid={!!formErrors.localitate}
                        />
                        {formErrors.localitate && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.localitate}</p>
                        )}
                    </div>
                </div>

                {/* Nume */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="nume" className="text-left pt-2">Nume *</Label>
                    <div className="col-span-3">
                        <Input
                            id="nume"
                            value={formData.nume}
                            onChange={(e) => setFormData(prev => ({ ...prev, nume: e.target.value }))}
                            invalid={!!formErrors.nume}
                        />
                        {formErrors.nume && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.nume}</p>
                        )}
                    </div>
                </div>

                {/* Adresa */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="adresa" className="text-left pt-2">Adresa *</Label>
                    <div className="col-span-3">
                        <Input
                            id="adresa"
                            value={formData.adresa}
                            onChange={(e) => setFormData(prev => ({ ...prev, adresa: e.target.value }))}
                            invalid={!!formErrors.adresa}
                        />
                        {formErrors.adresa && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.adresa}</p>
                        )}
                    </div>
                </div>

                {/* Contact */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="contact" className="text-left pt-2">Contact</Label>
                    <div className="col-span-3">
                        <Input
                            id="contact"
                            value={formData.contact}
                            onChange={(e) => setFormData(prev => ({ ...prev, contact: e.target.value }))}
                            invalid={!!formErrors.contact}
                        />
                        {formErrors.contact && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.contact}</p>
                        )}
                    </div>
                </div>

                {/* Telefon */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="telefon" className="text-left pt-2">Telefon</Label>
                    <div className="col-span-3">
                        <Input
                            id="telefon"
                            value={formData.telefon}
                            onChange={(e) => setFormData(prev => ({ ...prev, telefon: e.target.value }))}
                            invalid={!!formErrors.telefon}
                        />
                        {formErrors.telefon && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.telefon}</p>
                        )}
                    </div>
                </div>

                {/* Email */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="email" className="text-left pt-2">Email</Label>
                    <div className="col-span-3">
                        <Input
                            id="email"
                            value={formData.email}
                            onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
                            invalid={!!formErrors.email}
                        />
                        {formErrors.email && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.email}</p>
                        )}
                    </div>
                </div>
            </div>
        </Dialog>
    );
};

export const CreateDestinatarDialog = memo(CreateDestinatarDialogComponent);

// Edit Dialog Component
const EditDestinatarDialogComponent = ({ visible, onVisibleChange, destinatar, onSuccess, onFailed}: EditDialogProps) => {
    console.log("EditDestinatarDialog : rendered", visible);
    const [formData, setFormData] = useState<DestinatarData>({
        localitate_id: 0,
        localitate: '',
        nume: '',
        adresa: '',
        contact: null,
        telefon: null,
        email: null
    });
    const [formLoading, setFormLoading] = useState(false);
    const [formErrors, setFormErrors] = useState<{[key: string]: string}>({});
    const [localitateSearch, setLocalitateSearch] = useState('');

    // Update form data when dialog becomes visible with new destinatar data
    useEffect(() => {
        if (visible && destinatar) {
            startTransition(() => {
                setFormData({
                    localitate_id: destinatar.localitate_id || 0,
                    localitate: destinatar.localitate || '',
                    nume: destinatar.nume || '',
                    adresa: destinatar.adresa || '',
                    contact: destinatar.contact || '',
                    telefon: destinatar.telefon || '',
                    email: destinatar.email || ''
                });
                setLocalitateSearch(destinatar.localitate || '');
                setFormErrors({});
            });
        }
    }, [visible, destinatar]);

    const handleLocalitateSelect = useCallback((option: AutocompleteOption) => {
        setFormData(prev => ({ ...prev, localitate_id: Number(option.value) }));
        setFormErrors(prev => {
            const newErrors = { ...prev };
            delete newErrors.localitate;
            return newErrors;
        });
    }, []);

    const handleLocalitateNoResults = useCallback(() => {
        setFormData(prev => ({ ...prev, localitate_id: 0 }));
    }, []);

    const handleSubmit = async () => {
        if (!destinatar) return;

        const errors = validateForm(formData);
        
        if (Object.keys(errors).length > 0) {
            setFormErrors(errors);
            
            // Focus on first error field
            const errorFieldMap: Record<string, string> = {
                localitate: 'localitate',
                nume: 'nume',
                adresa: 'adresa',
                contact: 'contact',
                telefon: 'telefon',
                email: 'email'
            };
            
            const firstErrorKey = Object.keys(errors)[0];
            const inputId = errorFieldMap[firstErrorKey];
            
            if (inputId) {
                setTimeout(() => {
                    const element = document.getElementById(inputId);
                    if (element) {
                        element.focus();
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 100);
            }
            
            return;
        }
        
        setFormLoading(true);
        try {
            console.log('before updateDestinatar...');
            const response = await updateDestinatar(destinatar.id || 0, formData);
            console.log('after updateDestinatar...');
            
            // Close dialog and reset loading in a single batch
            startTransition(() => {
                onVisibleChange(false);
                setFormLoading(false);
            });
            
            // Call callbacks after state is settled to prevent parent re-renders while visible
            if(response.success){
                onSuccess?.();
            }
            else 
                onFailed?.(response.message || 'Eroare actualizare destinatar');
        } catch (error) {
            console.error('Error updating destinatar:', error);
            startTransition(() => {
                onVisibleChange(false);
                setFormLoading(false);
            });
            onFailed?.(error instanceof Error ? error.message : 'Eroare actualizare destinatar');
        }
    };

    const footerContent = (
        <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => onVisibleChange(false)} disabled={formLoading}>
                Anuleaza
            </Button>
            <Button onClick={handleSubmit} disabled={formLoading}>
                {formLoading ? 'Se actualizeaza...' : 'Actualizeaza'}
            </Button>
        </div>
    );

    return (
        <Dialog 
            visible={visible}
            onHide={() => {if (!visible) return; onVisibleChange(false); }}
            header="Editare destinatar"
            footer={footerContent}
            modal={true}
            style={{ width: '50vw' }} 
            breakpoints={{ '960px': '75vw', '641px': '100vw' }}
        >
            <div className="grid gap-4 py-4">
                {/* Localitate */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="localitate" className="text-left pt-2">Localitate *</Label>
                    <div className="col-span-3">
                        <Autocomplete
                            id="localitate"
                            text={localitateSearch}
                            onChange={setLocalitateSearch}
                            onSelect={handleLocalitateSelect}
                            onOpen={handleLocalitateNoResults}
                            onNoResults={handleLocalitateNoResults}
                            searchUrl="/commons/localitati"
                            placeholder="Caută localitate..."
                            minSearchLength={3}
                            invalid={!!formErrors.localitate}
                        />
                        {formErrors.localitate && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.localitate}</p>
                        )}
                    </div>
                </div>

                {/* Nume */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="nume" className="text-left pt-2">Nume *</Label>
                    <div className="col-span-3">
                        <Input
                            id="nume"
                            value={formData.nume}
                            onChange={(e) => setFormData(prev => ({ ...prev, nume: e.target.value }))}
                            invalid={!!formErrors.nume}
                        />
                        {formErrors.nume && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.nume}</p>
                        )}
                    </div>
                </div>

                {/* Adresa */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="adresa" className="text-left pt-2">Adresa *</Label>
                    <div className="col-span-3">
                        <Input
                            id="adresa"
                            value={formData.adresa}
                            onChange={(e) => setFormData(prev => ({ ...prev, adresa: e.target.value }))}
                            invalid={!!formErrors.adresa}
                        />
                        {formErrors.adresa && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.adresa}</p>
                        )}
                    </div>
                </div>

                {/* Contact */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="contact" className="text-left pt-2">Contact</Label>
                    <div className="col-span-3">
                        <Input
                            id="contact"
                            value={formData.contact}
                            onChange={(e) => setFormData(prev => ({ ...prev, contact: e.target.value }))}
                            invalid={!!formErrors.contact}
                        />
                        {formErrors.contact && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.contact}</p>
                        )}
                    </div>
                </div>

                {/* Telefon */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="telefon" className="text-left pt-2">Telefon</Label>
                    <div className="col-span-3">
                        <Input
                            id="telefon"
                            value={formData.telefon}
                            onChange={(e) => setFormData(prev => ({ ...prev, telefon: e.target.value }))}
                            invalid={!!formErrors.telefon}
                        />
                        {formErrors.telefon && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.telefon}</p>
                        )}
                    </div>
                </div>

                {/* Email */}
                <div className="grid grid-cols-4 items-start gap-4">
                    <Label htmlFor="email" className="text-left pt-2">Email</Label>
                    <div className="col-span-3">
                        <Input
                            id="email"
                            value={formData.email}
                            onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
                            invalid={!!formErrors.email}
                        />
                        {formErrors.email && (
                            <p className="text-red-500 text-sm mt-1">{formErrors.email}</p>
                        )}
                    </div>
                </div>
            </div>
        </Dialog>
    );
};

export const EditDestinatarDialog = memo(EditDestinatarDialogComponent);

// Delete Confirmation Dialog
const DeleteDestinatarDialogComponent = ({ visible, onVisibleChange, destinatar, onSuccess, onFailed }: DeleteDialogProps) => {
    const [formLoading, setFormLoading] = useState(false);
    console.log("DeleteDestinatarDialog : rendered");
    const handleConfirm = async () => {
        if (!destinatar) return;

        setFormLoading(true);
        try {
            const response = await deleteDestinatar(destinatar.id || 0);
            
            // Close dialog and reset loading in a single batch
            startTransition(() => {
                onVisibleChange(false);
                setFormLoading(false);
            });
            
            if(response.success)
                onSuccess?.();
            else 
                onFailed?.(response.message || 'Eroare stergere destinatar');
        } catch (error) {
            console.error('Eroare stergere destinatar:', error);
            startTransition(() => {
                onVisibleChange(false);
                setFormLoading(false);
            });
            onFailed?.(error instanceof Error ? error.message : 'Eroare stergere destinatar');
        }
    };

    const footerContent = (
        <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => onVisibleChange(false)} disabled={formLoading}>
                Anuleaza
            </Button>
            <Button variant="destructive" onClick={handleConfirm} disabled={formLoading}>
                {formLoading ? 'Se sterge...' : 'Sterge'}
            </Button>
        </div>
    );

    return (
        <Dialog 
            visible={visible}
            onHide={() => {if (!visible) return; onVisibleChange(false); }}
            header="Confirmare stergere"
            footer={footerContent}
            modal={true}
            style={{ width: '50vw' }} 
            breakpoints={{ '960px': '75vw', '641px': '100vw' }}
        >
            <div>
                Sunteti sigur ca doriti sa stergeti destinatarul <strong>{destinatar?.nume}</strong>?
                <br />
                Aceasta actiune nu poate fi anulata.
            </div>
        </Dialog>
    );
};

export const DeleteDestinatarDialog = memo(DeleteDestinatarDialogComponent);

// View Details Dialog
const ViewDestinatarDialogComponent = ({ visible, onVisibleChange, destinatar, onEdit, onDelete }: ViewDialogProps) => {
    const handleClose = useCallback(() => {
        onVisibleChange(false);
    }, [onVisibleChange]);

    const handleEdit = useCallback(() => {
        onVisibleChange(false);
        if (destinatar) onEdit?.(destinatar);
    }, [onVisibleChange, destinatar, onEdit]);

    const handleDelete = useCallback(() => {
        onVisibleChange(false);
        if (destinatar) onDelete?.(destinatar);
    }, [onVisibleChange, destinatar, onDelete]);

    const footerContent = (
        <div className="flex justify-end gap-2">
            <Button variant="destructive" onClick={handleDelete}>
                <Trash2 className="h-4 w-4" />
                Sterge
            </Button>
            <Button onClick={handleEdit}>
                <Edit className="h-4 w-4" />
                Editeaza
            </Button>
            <Button variant="outline" onClick={handleClose}>
                <LucideShieldClose className="h-4 w-4" />
                Inchide
            </Button>
        </div>
    );

    if (!destinatar) {
        return null;
    }

    return (
        <Dialog
            visible={visible}
            onHide={() => {if (!visible) return; onVisibleChange(false); }}
            header="Detalii destinatar"
            footer={footerContent}
            modal={true}
            style={{ width: '50vw' }}
            breakpoints={{ '960px': '75vw', '641px': '100vw' }}
        >
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 py-4">
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold border-b pb-2">Informatii generale</h3>
                    <div className="grid grid-cols-2 gap-2 text-sm">
                        <div className="font-medium">Nume:</div>
                        <div>{destinatar.nume}</div>
                        <div className="font-medium">Judet:</div>
                        <div>{destinatar.judet}</div>
                        <div className="font-medium">Localitate:</div>
                        <div>{destinatar.localitate}</div>
                        <div className="font-medium">Adresa:</div>
                        <div>{destinatar.adresa}</div>
                        <div className="font-medium">Contact:</div>
                        <div>{destinatar.contact || '-'}</div>
                        <div className="font-medium">Telefon:</div>
                        <div>{destinatar.telefon || '-'}</div>
                        <div className="font-medium">Email:</div>
                        <div>{destinatar.email || '-'}</div>
                    </div>
                </div>

                <div className="space-y-4">
                    <h3 className="text-lg font-semibold border-b pb-2">Informatii sistem</h3>
                    <div className="grid grid-cols-2 gap-2 text-sm">
                        <div className="font-medium">Data creare:</div>
                        <div>{formatDate(destinatar.created_at || '')}</div>
                        <div className="font-medium">Creat de:</div>
                        <div>{destinatar.created_by || '-'}</div>
                        <div className="font-medium">Ultima modificare:</div>
                        <div>{formatDate(destinatar.updated_at || '')}</div>
                        <div className="font-medium">Modificat de:</div>
                        <div>{destinatar.updated_by || '-'}</div>
                    </div>
                </div>
            </div>
        </Dialog>
    );
};

export const ViewDestinatarDialog = memo(ViewDestinatarDialogComponent);
