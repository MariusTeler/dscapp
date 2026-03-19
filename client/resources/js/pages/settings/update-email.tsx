import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { update } from '@/routes/email';
import { Form, Head } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';

export default function UpdateEmail() {
    return (
        <AuthLayout
            title="Adaugă adresă de email"
            description="Pentru securitatea contului tău, te rugăm să adaugi o adresă de email pentru recuperarea parolei și autentificarea în doi pași"
        >
            <Head title="Adaugă email" />

            <div className="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
                <div className="flex gap-3">
                    <AlertCircle className="h-5 w-5 text-amber-600 dark:text-amber-500" />
                    <div className="flex-1">
                        <h3 className="text-sm font-medium text-amber-900 dark:text-amber-100">
                            Acțiune obligatorie
                        </h3>
                        <p className="mt-1 text-sm text-amber-700 dark:text-amber-200">
                            Contul tău nu are o adresă de email validă. Pentru a continua, te rugăm să adaugi o adresă de email care va fi folosită pentru recuperarea parolei și autentificarea în doi pași.
                        </p>
                    </div>
                </div>
            </div>

            <Form
                {...update.form()}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Adresă de email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    placeholder="exemplu@email.com"
                                />
                                <InputError message={errors.email} />
                                <p className="text-sm text-muted-foreground">
                                    Vei primi un email de verificare la această adresă.
                                </p>
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                Salvează email
                            </Button>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
