import React, { useRef, useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { BeatLoader } from '../../../node_modules/react-spinners';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle
} from '@/shadcn/ui/card';
import { Input } from '@/shadcn/ui/input';
import { Separator } from '@/shadcn/ui/separator';
import { Button } from '@/shadcn/ui/button';
import { HomeIcon, ExitIcon } from '@radix-ui/react-icons';
import { ScrollArea } from "@/shadcn/ui/scroll-area"
import { Alert, AlertDescription, AlertTitle } from "@/shadcn/ui/alert"
import { Head } from '@inertiajs/react';

export default function ScanProduct() {
    const [code, setCode] = useState('');
    const [product, setProduct] = useState(null);
    const [error, setError] = useState('');
    const [scannedCodes, setScannedCodes] = useState([]);
    const [status, setStatus] = useState('')
    const [isLoading, setIsLoading] = useState(false);
    const [sessionId, setSessionId] = useState(null);
    const [mensajeAlerta, setMensajeAlerta] = useState('');
    const [detalleAlerta, setDetalleAlerta] = useState('');
    const [estadoAlerta, setEstadoAlerta] = useState(false);
    const [timeout, setTimeouts] = useState(500);
    const inputRef = useRef<HTMLInputElement>(null); // Especifica que inputRef es una referencia a un elemento HTMLInputElement


    const handleChange = (e) => {
        setCode(e.target.value);

    };
    // Método para manejar el escaneo de códigos de barra
    // Referencia para el código escaneado más reciente
    const scannedCodeRef = useRef('');

    // Referencia para el timer de búsqueda
    const searchTimer = useRef<NodeJS.Timeout | null>(null); // Inicializa como NodeJS.Timeout | null

    const handleScan = async (e) => {
        if (e.key === 'Enter') {
            const scannedCode = e.target.value.trim(); // Obtener el código escaneado limpio

            if (scannedCode) {
                // Verificar si el código escaneado es diferente al último escaneado

                // Actualizar la referencia del código escaneado más reciente
                scannedCodeRef.current = scannedCode;

                // Limpiar el timer de búsqueda previo
                if (searchTimer.current) {
                    clearTimeout(searchTimer.current);
                }

                // Iniciar un nuevo timer para la búsqueda después de 200 ms (ajustable)
                searchTimer.current = setTimeout(async () => {
                    setIsLoading(true);
                    inputRef.current.disabled = true; // Deshabilitar el input

                    if (scannedCode) {
                        try {
                            // Obtener datos del producto escaneado desde el servidor
                            const response = await axios.get(`/api/product/${scannedCode}`);
                            // Actualizar la lista de los últimos códigos escaneados
                            fetchLatestScannedCodes();
                            getSesion();
                            setCode(''); // Limpiar el código escaneado después de la búsqueda exitosa
                            setIsLoading(false);
                        } catch (err) {
                            // Manejar errores en la obtención de datos del producto
                            setError('Código de etiqueta no válido');
                            setDetalleAlerta("Revise el código escaneado.");
                            setCode(''); // Limpiar el código escaneado en caso de error
                            alertas();
                            console.error(err);
                            setIsLoading(false);
                        }
                    }
                    inputRef.current.disabled = false; // Habilitar el input nuevamente
                    inputRef.current.focus(); // Enfocar el input
                }, 200); // Tiempo de espera en milisegundos antes de realizar la búsqueda

            } else {
                inputRef.current.focus(); // Enfocar el input
                console.log("esta vacio")
                setIsLoading(false);
                setError('Código de etiqueta no válido');
                setDetalleAlerta("Revise el código escaneado.");
                setCode(''); // Limpiar el código escaneado en caso de error

            }

        }
    };
    // Limpiar el timer si el componente se desmonta o el código cambia
    useEffect(() => {
        return () => {
            if (searchTimer.current) {
                clearTimeout(searchTimer.current);
            }
        };
    }, []);

    useEffect(() => {
        // Focaliza el input cuando el componente se monta
        if (inputRef.current) {
            inputRef.current.focus();
        }
    }, []);
    const alertas = () => {
        setEstadoAlerta(true)
        setMensajeAlerta(error);
        setTimeout(() => {
            setEstadoAlerta(false)
        }, timeout);
    }
    const getSesion = async () => {

        try {
            // Obtener datos del producto escaneado desde el servidor
            const response = await axios.get(`/api/sesion/`);
            const productData = response.data;

            // Actualizar el estado del producto y limpiar cualquier error previo
            setProduct(productData);
            setError('');

            // Actualizar la lista de los últimos códigos escaneados
            fetchLatestScannedCodes();
            setCode('')
        } catch (err) {
            // Manejar errores en la obtención de datos del producto
            setProduct(null);
            setError("No se consiguio sessionid");
            setDetalleAlerta("Reinicie el Navegador");
            alertas()
            console.error(err);
        }

    };


    // Método para obtener la lista de los últimos códigos escaneados
    const fetchLatestScannedCodes = async () => {
        try {
            // Solicitar la lista de los últimos códigos escaneados desde el servidor
            const response = await axios.get('/api/scanned-codes/latest');


            // Verificar si hay códigos inválidos en la respuesta
            if (response.data.EAN13INVALIDO || response.data.EAN128INVALIDO) {
                // Mostrar alerta o manejar de acuerdo a tus requerimientos
                console.log("Código de etiqueta no válido");
                setError("Código de etiqueta no válido");
                setDetalleAlerta("Revise el código escaneado.");
                alertas()
                setEstadoAlerta(true);
            } else {
                // Actualizar el estado con los códigos escaneados y el ID de la sesión
                setScannedCodes(response.data.scanned_codes);
            }



            setSessionId(response.data.scan_session_id); // Guardar el ID de la sesión activa

            // Si hay códigos escaneados, actualizar el estado del componente
            if (response.data.scanned_codes.length > 0) {
                setStatus('FINALIZAR')
            }
            if (response.data.scan_session_id) {
                setStatus('INICIAR')
            }
        } catch (err) {
            // Manejar errores en la obtención de los códigos escaneados
            console.error('Error fetching latest scanned codes:', err);
        }
    };

    // useEffect para obtener la lista inicial de códigos escaneados cuando el componente se monta
    useEffect(() => {
        fetchLatestScannedCodes();
        getSesion()
    }, []);


    // Método para manejar el clic del botón de estado
    const handleButtonClick = async () => {
        if (status === '') {
            await getStatus('INICIAR');

        } else if (status === 'INICIAR') {

            await getStatus('FINALIZAR');

            clean();
        } else if (status === 'FINALIZAR') {
            await getStatus('');

            clean();
        }
    };


    // Método para limpiar el estado de la aplicación
    const clean = () => {
        setProduct(null);
        setCode('');
        setStatus('INICIAR');
        setScannedCodes([]);
        setError('');
        console.log('LIMPEAR');
    };

    const getStatus = useCallback((newStatus) => {
        setIsLoading(true);
        console.log(newStatus)
        if (newStatus === 'INICIAR') {
            axios.post('/scan-session/start')
                .then(response => {
                    setSessionId(response.data.id);
                    setStatus(newStatus);
                    console.log(response);
                    // Si la sesión se inicia correctamente, no necesitas hacer nada especial con el input
                })
                .catch(error => {
                    // Manejar el caso donde ya hay una sesión activa
                    if (error.response && error.response.status === 400) {
                        console.error('Ya hay una sesión de escaneo activa.');
                        // Aquí deberías habilitar el input nuevamente o mostrar un mensaje al usuario
                    } else {
                        console.error('Error al iniciar la sesión de escaneo:', error);
                    }
                })
                .finally(() => setIsLoading(false));

        } else if (newStatus === 'FINALIZAR') {

            if (sessionId && scannedCodes.length > 0) {
                axios.post(`/scan-session/end/${sessionId}`, { total_scans: scannedCodes.length })
                    .then(() => {
                        setSessionId(null);
                        setStatus('');
                    })
                    .catch(error => {
                        console.error('Error al finalizar la sesión de escaneo:', error);
                    })
                    .finally(() => setIsLoading(false));
            } else if (sessionId && scannedCodes.length < 1) {
                setEstadoAlerta(true)
                setError('No se puede finalizar');
                setDetalleAlerta("Debes al menos escanear  una etiqueta");
                console.error('No se puede finalizar porque no hay etiquetas registras.');

                setIsLoading(false)
                setTimeout(() => {
                    setEstadoAlerta(false)
                }, timeout);

            } else {
                setEstadoAlerta(true)
                console.error('No se puede finalizar una sesión sin un ID de sesión válido.');
                setError('No se puede finalizar');
                setDetalleAlerta("Una sesión sin un ID de sesión válido.");
                setIsLoading(false);
                setTimeout(() => {
                    setEstadoAlerta(false)
                }, timeout);

            }
        }
    }, [sessionId, scannedCodes.length]);

    useEffect(() => {
        // Inicializar el estado desde el almacenamiento local
        const savedScannedCodes = JSON.parse(localStorage.getItem('scannedCodes')) || [];
        const savedSessionId = localStorage.getItem('sessionId');
        const savedStatus = localStorage.getItem('status') || '';
    
        // Convertir sessionId a null si es null o undefined
        const initialSessionId = savedSessionId !== 'undefined' ? savedSessionId : null;
    
        setScannedCodes(savedScannedCodes);
        setSessionId(initialSessionId);
        setStatus(savedStatus);
    }, []);
    
    useEffect(() => {
        // Guardar códigos escaneados e información de la sesión en el almacenamiento local
        localStorage.setItem('scannedCodes', JSON.stringify(scannedCodes));
        localStorage.setItem('sessionId', sessionId);
        localStorage.setItem('status', status);
    }, [scannedCodes, sessionId, status]);
    


    return (
        <section className="bg-gray-100">
             <Head title="ETIQUETAS" />
            {estadoAlerta && (
                <div className="bg-indigo-900 text-center py-4 lg:px-4">
                    <div className="p-2 bg-indigo-400 items-center text-indigo-100 leading-none lg:rounded-full flex lg:inline-flex" role="alert">
                        <svg className="fill-current h-6 w-6 text-orange-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z" /></svg>
                        {error && (
                            <span className="flex rounded-full bg-red-500 uppercase px-2 py-1 text-xs text-white font-bold mr-3">{error}</span>
                        )}
                        {detalleAlerta && (
                            <span className="font-semibold mr-2 text-left text-white flex-auto">{detalleAlerta}</span>
                        )}

                        <svg className="fill-current opacity-75 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M12.95 10.707l.707-.707L8 4.343 6.586 5.757 10.828 10l-4.242 4.243L8 15.657l4.95-4.95z" /></svg>
                    </div>
                </div>

            )}


            <div className="py-1 px-2 mx-auto max-w-screen-xl text-center lg:py-5 bg-gray-20">
                <div className="flex justify-center items-center gap-4 mt-1">
                    <h1 className="mb-2 text-1xl font-extrabold tracking-tight leading-none md:text-1xl lg:text-1xl text-blue">VALIDADOR ETIQUETAS</h1>
                    {status === '' ? (
                        <>
                            <Input
                                disabled
                                placeholder="Para comenzar a scannear productos, debe presionar el boton INICIAR"
                                className="text-lg text-center border-2 border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500 w-full md:w-96"

                            />
                        </>
                    ) : (
                        <>
                            <div>
                                <input
                                    value={code}
                                    onChange={handleChange}
                                    onKeyDown={handleScan}
                                    ref={inputRef}
                                    autoFocus
                                    placeholder="Ingrese el código de la etiqueta aquí"
                                    className="text-lg text-center border-2 border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500 w-full md:w-96"
                                />

                            </div>
                        </>

                    )}

                    {isLoading && (
                        <div className="flex justify-center mt-4">
                            <BeatLoader />
                        </div>
                    )}
                    <Button onClick={handleButtonClick} disabled={isLoading}>
                        {status === '' ? (
                            <>
                                <HomeIcon className="mr-2 h-4 w-4" /> INICIAR
                            </>
                        ) : status === 'INICIAR' ? (
                            <>
                                <ExitIcon className="mr-2 h-4 w-4" /> FINALIZAR
                            </>
                        ) : (
                            <>
                                <HomeIcon className="mr-2 h-4 w-4" /> INICIAR
                            </>
                        )}
                    </Button>
                </div>
            </div>
            {scannedCodes.length > 0 ? (
                <div className="bg-gray-900 py-2 px-2 mx-auto max-w-screen-xl  lg:py-10 rounded-md border">
                    <div className="grid mt-4 content-normal" style={{ gridTemplateColumns: "0.8fr 0.5fr 3fr" }}>
                        <div className="col-span-1">
                            <ScrollArea className="h-[490px] max-w-[590px] rounded-md border p-4 bg-slate-50 overflow-auto">
                                <h4 className="mb-4 text-sm font-medium leading-none">  <CardDescription className='text-[#322b9d]'>EAN 128</CardDescription></h4>
                                {scannedCodes.length > 0 ? (
                                    scannedCodes.map((code, index) => (
                                        <div key={index} className="md:text-3xl">
                                            {code.lote === 'INVALIDO' ? (
                                                <span className="text-red-500 italic">{code.EAN128}</span>
                                            ) : (
                                                <span>{code.EAN128}</span>
                                            )}
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-lg text-red-500">Aún no hay productos escaneados. Por favor, escanee uno.</div>
                                )}
                            </ScrollArea>

                        </div>
                        <div className="col-span-1  px-2">
                            <ScrollArea className="h-[490px] max-w-[247px] rounded-md border p-4 bg-slate-50 overflow-auto">
                                <h4 className="mb-4 text-sm font-medium leading-none"> <CardDescription className='text-[#322b9d]'>EAN 13</CardDescription>  </h4>
                                {scannedCodes.length > 0 ? (
                                    scannedCodes.map((code, index) => (
                                        <div key={index} className="md:text-3xl">
                                            {code.lote === 'INVALIDO' ? (
                                                <span className="text-red-500 italic">{code.EAN13}</span>
                                            ) : (
                                                <span>{code.EAN13}</span>
                                            )}
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-lg text-red-500">Aún no hay productos escaneados. Por favor, escanee uno.</div>
                                )}
                            </ScrollArea>
                        </div>
                        <div className="col-span-1">
                            {product && (
                                <Card>
                                    <CardHeader>
                                        <CardDescription className="mb-4 text-sm font-medium leading-none text-[#322b9d]">PRODUCTO ENCONTRADO</CardDescription>
                                        <CardDescription>
                                            <b className="md:text-2xl text-[#322b9d]">Codigo:</b> <strong className="md:text-4xl text-[#322b9d]">{product.code}</strong>
                                        </CardDescription>
                                        <Separator />
                                        <CardTitle className="md:text-3xl flex "> {product.producto}</CardTitle>
                                    </CardHeader>
                                    {product.lote != 0 && (
                                        <>
                                            <Separator />
                                            <CardContent className="grid gap-4">
                                                <CardDescription>
                                                    <b className="md:text-2xl text-[#322b9d]">N° Lote:</b>
                                                    <strong className="md:text-4xl text-[#322b9d]">{product.lote}</strong>
                                                </CardDescription>
                                            </CardContent>
                                        </>
                                    )}



                                </Card>
                            )}
                        </div>
                    </div>
                </div>) : (
                <div className=" py-2 px-2 mx-auto max-w-screen-xl  lg:py-10 rounded-md border">
                    {status === '' ? (
                        <>

                        </>
                    ) : (
                        <>
                            {error ? (

                                <div className="text-lg text-center">{error} Scanner un etiqueta valida para comenzar</div>
                            ) : (
                                <div className="text-lg text-center">Scanner la primera etiqueta  para comenzar</div>
                            )}

                        </>

                    )}
                </div>
            )}

        </section>
    );

}
