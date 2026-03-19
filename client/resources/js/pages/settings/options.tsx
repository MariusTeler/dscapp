import { Head, router, usePage } from '@inertiajs/react';
import { useRef, useState, useEffect } from 'react';
import { type BreadcrumbItem, type SharedData } from '@/types';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { RadioButton } from '@/components/ui/primereact/radio-button';
import { Textarea } from '@/components/ui/textarea';
import HeadingSmall from '@/components/heading-small';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit as editOptions, update as updateOptions } from '@/routes/options';
import { type UserPrefs } from '@/types';
import Toast, { ToastRef, showSuccess } from '@/components/ui/primereact/toast';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Optiuni',
        href: editOptions().url,
    },
];

type PrintOption = 1 | 2 | 3 | 4 | 5 | 6 | 7 | 10;

export default function Optiuni() {
    const { prefs } = usePage<SharedData>().props;
    const print: number = (prefs as UserPrefs)?.print || 2;
    const def_sms: boolean = (prefs as UserPrefs)?.def_sms || false;
    const def_obsv: string = (prefs as UserPrefs)?.def_obsv || '';
    const toast = useRef<ToastRef>(null);
    const [justSucceeded, setJustSucceeded] = useState(false);

    console.log('Loaded preferences:', { print, def_sms, def_obsv });
    useEffect(() => {
        if (justSucceeded) {
            showSuccess(toast, 'Salvat', 'Optiunile au fost actualizate cu succes.');
            const timer = setTimeout(() => setJustSucceeded(false), 100);
            return () => clearTimeout(timer);
        }
    }, [justSucceeded]);

    const [printOption, setPrintOption] = useState<PrintOption>(print as PrintOption);
    const [smsImplicit, setSmsImplicit] = useState<boolean>(def_sms);
    const [textObservatii, setTextObservatii] = useState<string>(def_obsv);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.patch(updateOptions().url, {
            print_awb: printOption,
            def_sms: smsImplicit,
            def_obsv: textObservatii,
        }, {
            onSuccess: () => {
                setJustSucceeded(true);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Toast ref={toast} />
            <Head title="Optiuni" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Optiuni" />

                    <Card>
                        <CardContent className="pt-6">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Optiuni AWB */}
                                <div className="space-y-3">
                                    <Label className="text-muted-foreground text-sm font-medium">
                                        Optiuni printare AWB:
                                    </Label>
                                    <div className="space-y-2 mt-3">
                                        <label htmlFor="print" className="flex items-center gap-3 cursor-pointer">
                                            <RadioButton
                                                inputId="print"
                                                name="printOption"
                                                value={2}
                                                checked={printOption === 2}
                                                onChange={(e) => setPrintOption(e.value as PrintOption)}
                                            />
                                            <span className="text-sm font-medium">Print</span>
                                        </label>
                                        <label htmlFor="master_a4_4_copies" className="flex items-center gap-3 cursor-pointer">
                                            <RadioButton
                                                inputId="master_a4_4_copies"
                                                name="printOption"
                                                value={1}
                                                checked={printOption === 1}
                                                onChange={(e) => setPrintOption(e.value as PrintOption)}
                                            />
                                            <span className="text-sm font-medium">Printeaza master pe A4 si cate 4 puisori pe A4</span>
                                        </label>
                                        <label htmlFor="master_a4_2_copies" className="flex items-center gap-3 cursor-pointer">
                                            <RadioButton
                                                inputId="master_a4_2_copies"
                                                name="printOption"
                                                value={7}
                                                checked={printOption === 7}
                                                onChange={(e) => setPrintOption(e.value as PrintOption)}
                                            />
                                            <span className="text-sm font-medium">Printeaza master pe A4 si cate 2 puisori pe A4</span>
                                        </label>
                                        <label htmlFor="stickers_70x100" className="flex items-center gap-3 cursor-pointer">
                                            <RadioButton
                                                inputId="stickers_70x100"
                                                name="printOption"
                                                value={3}
                                                checked={printOption === 3}
                                                onChange={(e) => setPrintOption(e.value as PrintOption)}
                                            />
                                            <span className="text-sm font-medium">Printeaza puisori pe etichete autocolante 70x100 mm</span>
                                        </label>
                                        <label htmlFor="stickers_100x150" className="flex items-center gap-3 cursor-pointer">
                                            <RadioButton
                                                inputId="stickers_100x150"
                                                name="printOption"
                                                value={4}
                                                checked={printOption === 4}
                                                onChange={(e) => setPrintOption(e.value as PrintOption)}
                                            />
                                            <span className="text-sm font-medium">Printeaza puisori pe etichete autocolante 100x150 mm</span>
                                        </label>
                                        <label htmlFor="master_stickers_100x150" className="flex items-center gap-3 cursor-pointer">
                                            <RadioButton
                                                inputId="master_stickers_100x150"
                                                name="printOption"
                                                value={5}
                                                checked={printOption === 5}
                                                onChange={(e) => setPrintOption(e.value as PrintOption)}
                                            />
                                            <span className="text-sm font-medium">Printeaza master si puisori pe etichete autocolante 100x150 mm</span>
                                        </label>
                                        <label htmlFor="master_stickers_a4" className="flex items-center gap-3 cursor-pointer">
                                            <RadioButton
                                                inputId="master_stickers_a4"
                                                name="printOption"
                                                value={6}
                                                checked={printOption === 6}
                                                onChange={(e) => setPrintOption(e.value as PrintOption)}
                                            />
                                            <span className="text-sm font-medium">Printeaza master si puisori pe etichete autocolante A4</span>
                                        </label>
                                        <label htmlFor="custom" className="flex items-center gap-3 cursor-pointer">
                                            <RadioButton
                                                inputId="custom"
                                                name="printOption"
                                                value={10}
                                                checked={printOption === 10}
                                                onChange={(e) => setPrintOption(e.value as PrintOption)}
                                            />
                                            <span className="text-sm font-medium">Printeaza la alegere : master vs master+puisori</span>
                                        </label>
                                    </div>
                                </div>

                                {/* SMS Implicit */}
                                <div className="space-y-3">
                                    <Label className="text-muted-foreground text-sm font-medium">
                                        Sms implicit
                                    </Label>
                                    <div className="flex items-center gap-6 mt-3">
                                        <label htmlFor="sms_da" className="flex items-center gap-3 cursor-pointer">
                                            <RadioButton
                                                inputId="sms_da"
                                                name="smsImplicit"
                                                value={true}
                                                checked={smsImplicit === true}
                                                onChange={(e) => setSmsImplicit(e.value)}
                                            />
                                            <span className="text-sm font-medium">Da</span>
                                        </label>
                                        <label htmlFor="sms_nu" className="flex items-center gap-3 cursor-pointer">
                                            <RadioButton
                                                inputId="sms_nu"
                                                name="smsImplicit"
                                                value={false}
                                                checked={smsImplicit === false}
                                                onChange={(e) => setSmsImplicit(e.value)}
                                            />
                                            <span className="text-sm font-medium">Nu</span>
                                        </label>
                                    </div>
                                </div>

                                {/* Text Implicit Observatii */}
                                <div className="space-y-3">
                                    <Label htmlFor="textObservatii" className="text-muted-foreground text-sm font-medium">
                                        Text implicit observatii
                                    </Label>
                                    <Textarea
                                        id="textObservatii"
                                        value={textObservatii}
                                        onChange={(e) => setTextObservatii(e.target.value)}
                                        placeholder="test"
                                        rows={4}
                                        className="resize-none"
                                    />
                                </div>

                                {/* Submit Button */}
                                <div className="flex justify-end">
                                    <Button type="submit">
                                        Salveaza
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
