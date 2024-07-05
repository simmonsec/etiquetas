// archivo: C:\laragon\www\laravelReact\resources\js\Pages\Welcome.tsx
import { Link, Head } from '@inertiajs/react';
import { PageProps } from '@/types';
import GuestLayout from '@/Layouts/GuestLayout';


export default function Welcome({ auth, laravelVersion, phpVersion }: PageProps<{ laravelVersion: string, phpVersion: string }>) {
    return (
        <GuestLayout  
        >
            <section className="bg-gray-900">
                <div className="py-8 px-4 mx-auto max-w-screen-xl text-center lg:py-16">
                    <h1 className="mb-4 text-4xl font-extrabold tracking-tight leading-none  md:text-5xl lg:text-6xl text-white">SISTEMAS DE ETIQUETAS</h1>
                    
                </div>
            </section>
            
        </GuestLayout>
    );
}
