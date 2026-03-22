import { useForm } from '@inertiajs/react';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { Password } from 'primereact/password';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        user: '',
        password: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/login');
    }

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-100">
            <div className="bg-white rounded-xl shadow-md p-8 w-full max-w-sm">
                <h1 className="text-2xl font-bold text-center mb-6 text-blue-700">
                    DSC Operator
                </h1>
                <form onSubmit={submit} className="flex flex-col gap-4">
                    <div className="flex flex-col gap-1">
                        <label className="text-sm font-medium">Utilizator</label>
                        <InputText
                            value={data.user}
                            onChange={(e) => setData('user', e.target.value)}
                            className={errors.user ? 'p-invalid' : ''}
                        />
                        {errors.user && <small className="text-red-500">{errors.user}</small>}
                    </div>
                    <div className="flex flex-col gap-1">
                        <label className="text-sm font-medium">Parolă</label>
                        <Password
                            value={data.password}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('password', e.target.value)}
                            feedback={false}
                            toggleMask
                            className={errors.password ? 'p-invalid' : ''}
                        />
                        {errors.password && <small className="text-red-500">{errors.password}</small>}
                    </div>
                    <Button
                        type="submit"
                        label="Autentificare"
                        loading={processing}
                        className="mt-2"
                    />
                </form>
            </div>
        </div>
    );
}
