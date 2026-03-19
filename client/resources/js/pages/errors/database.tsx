import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { AlertCircle, RefreshCw, Home } from 'lucide-react';

interface DatabaseErrorProps {
    message?: string;
    error?: string;
}

export default function DatabaseError({ message, error }: DatabaseErrorProps) {
    const handleRefresh = () => {
        router.reload();
    };

    const handleGoHome = () => {
        router.visit('/');
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 p-4">
            <Head title="Eroare baza de date" />
            
            <Card className="w-full max-w-2xl">
                <CardHeader>
                    <div className="flex items-center gap-4">
                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                            <AlertCircle className="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                Eroare de conexiune la baza de date
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Ne pare rău, a apărut o problemă cu baza de date
                            </p>
                        </div>
                    </div>
                </CardHeader>
                
                <CardContent className="space-y-6">
                    {message && (
                        <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <p className="text-sm text-red-800 dark:text-red-200">
                                {message}
                            </p>
                        </div>
                    )}

                    {error && (
                        <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <p className="text-xs font-mono text-gray-600 dark:text-gray-400 break-all">
                                {error}
                            </p>
                        </div>
                    )}

                    <div className="space-y-3">
                        <h3 className="font-semibold text-gray-900 dark:text-gray-100">
                            Ce poți face:
                        </h3>
                        <ul className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <li className="flex items-start gap-2">
                                <span className="text-blue-600 dark:text-blue-400 mt-0.5">•</span>
                                <span>Verificați conexiunea la internet</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="text-blue-600 dark:text-blue-400 mt-0.5">•</span>
                                <span>Reîncărcați pagina pentru a încerca din nou</span>
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="text-blue-600 dark:text-blue-400 mt-0.5">•</span>
                                <span>Contactați administratorul sistemului dacă problema persistă</span>
                            </li>
                        </ul>
                    </div>

                    <div className="flex gap-3 pt-4">
                        <Button
                            onClick={handleRefresh}
                            className="flex items-center gap-2"
                        >
                            <RefreshCw className="h-4 w-4" />
                            Reîncarcă pagina
                        </Button>
                        <Button
                            onClick={handleGoHome}
                            variant="outline"
                            className="flex items-center gap-2"
                        >
                            <Home className="h-4 w-4" />
                            Înapoi la pagina principală
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
