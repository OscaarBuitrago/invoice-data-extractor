import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type PageProps } from '@/types';
import { Head, useForm } from '@inertiajs/react';

export default function ConsultanciesCreate(_props: PageProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        tax_id: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('consultancies.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nueva asesoría
                </h2>
            }
        >
            <Head title="Nueva asesoría" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel htmlFor="name" value="Nombre" />
                                <TextInput
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoFocus
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="tax_id" value="CIF / NIF" />
                                <TextInput
                                    id="tax_id"
                                    value={data.tax_id}
                                    onChange={(e) => setData('tax_id', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.tax_id} className="mt-2" />
                            </div>

                            <PrimaryButton disabled={processing}>
                                Crear asesoría
                            </PrimaryButton>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
