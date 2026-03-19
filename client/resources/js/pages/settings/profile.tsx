import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { send } from '@/routes/verification';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useRef, useState, useEffect } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/profile';
import Toast, { ToastRef, showSuccess } from '@/components/ui/primereact/toast';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Date personale',
        href: edit().url,
    },
];

function ProfileFormContent({
    processing,
    recentlySuccessful,
    errors,
    auth,
    mustVerifyEmail,
    status,
    onSuccess,
}: {
    processing: boolean;
    recentlySuccessful: boolean;
    errors: Record<string, string>;
    auth: SharedData['auth'];
    mustVerifyEmail: boolean;
    status?: string;
    onSuccess: () => void;
}) {
    useEffect(() => {
        if (recentlySuccessful) {
            onSuccess();
        }
    }, [recentlySuccessful, onSuccess]);

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="contact">Contact</Label>

                <Input
                    id="nume"
                    className="mt-1 block w-full"
                    defaultValue={auth.user.nume}
                    name="nume"
                    required
                    placeholder="Persoana de contact"
                />

                <InputError
                    className="mt-2"
                    message={errors.nume}
                />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="phone">Telefon</Label>

                <Input
                    id="telefon"
                    className="mt-1 block w-full"
                    defaultValue={auth.user.telefon as string}
                    name="telefon"
                    placeholder="Telefon"
                />

                <InputError
                    className="mt-2"
                    message={errors.telefon}
                />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="email">Email</Label>

                <Input
                    id="email"
                    type="email"
                    className="mt-1 block w-full"
                    defaultValue={auth.user.email}
                    name="email"
                    required
                    placeholder="Email address"
                />

                <InputError
                    className="mt-2"
                    message={errors.email}
                />
            </div>

            {mustVerifyEmail &&
                auth.user.email_verified_at === null && (
                    <div>
                        <p className="-mt-4 text-sm text-muted-foreground">
                            Adresa de email nu este verificată.{' '}
                            <Link
                                href={send()}
                                as="button"
                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Apasă aici pentru a retrimite emailul de verificare.
                            </Link>
                        </p>

                        {status ===
                            'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                Un nou link de verificare a fost trimis la adresa ta de email.
                            </div>
                        )}
                    </div>
                )}

            <div className="flex items-center gap-4">
                <Button
                    disabled={processing}
                    data-test="update-profile-button"
                >
                    Salveaza
                </Button>
            </div>
        </>
    );
}

export default function Profile({
    mustVerifyEmail,
    status,
    expeditor_nume,
    expeditor_localitate,
    expeditor_adresa,
    expeditor_cui,
    expeditor_orc,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    expeditor_nume: string;
    expeditor_localitate: string;
    expeditor_adresa: string;
    expeditor_cui?: string;
    expeditor_orc?: string;
}) {
    const { auth } = usePage<SharedData>().props;
    const toast = useRef<ToastRef>(null);
    const [justSucceeded, setJustSucceeded] = useState(false);

    useEffect(() => {
        if (justSucceeded) {
            showSuccess(toast, 'Salvat', 'Profilul a fost actualizat cu succes.');
            const timer = setTimeout(() => setJustSucceeded(false), 100);
            return () => clearTimeout(timer);
        }
    }, [justSucceeded]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Toast ref={toast} />
            <Head title="Profile settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Informatii client"
                    />

                    <div className="grid gap-4 rounded-lg border p-4">
                        <div className="text-sm">
                            <span className="text-muted-foreground">Nume: </span>
                            <span className="font-medium">{expeditor_nume || '-'}</span>
                        </div>
                        <div className="text-sm">
                            <span className="text-muted-foreground">CUI: </span>
                            <span className="font-medium">{expeditor_cui || '-'}</span>
                        </div>
                        <div className="text-sm">
                            <span className="text-muted-foreground">O.R.C.: </span>
                            <span className="font-medium">{expeditor_orc || '-'}</span>
                        </div>
                        <div className="text-sm">
                            <span className="text-muted-foreground">Localitate: </span>
                            <span className="font-medium">{expeditor_localitate || '-'}</span>
                        </div>
                        <div className="grid gap-1">
                            <span className="text-muted-foreground">Adresa:</span>
                            <span className="font-medium">{expeditor_adresa || '-'}</span>
                        </div>
                    </div>

                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <ProfileFormContent
                                processing={processing}
                                recentlySuccessful={recentlySuccessful}
                                errors={errors}
                                auth={auth}
                                mustVerifyEmail={mustVerifyEmail}
                                status={status}
                                onSuccess={() => setJustSucceeded(true)}
                            />
                        )}
                    </Form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
