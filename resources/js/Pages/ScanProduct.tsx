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
    //011786122290095617000000102407
    //7861222900959
    const [code, setCode] = useState('');
    // Define el tipo para sesionEtiqueta
    type EtiquetaSesion = {
        code: string;
        producto: string;
        lote: string | number;
    } | null; // Permite null como un valor válido para este tipo

    const [sesionEtiqueta, setSesionEtiqueta] = useState<EtiquetaSesion>({
        code: '',
        producto: '',
        lote: 0
    });

    const [error, setError] = useState('');
    // Define el tipo de los códigos escaneados
    type ScannedCode = {
        code: string;
        EAN13: string;
        EAN14: string;
        EAN128: string;
        lote: string;
        producto: string;
        timestamp: number;
    };
    const [scannedCodes, setScannedCodes] = useState<ScannedCode[]>([]);
    const [status, setStatus] = useState('')
    const [StatusEtiqueta, setStatusEtiqueta] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [sessionId, setSessionId] = useState(null);
    const [mensajeAlerta, setMensajeAlerta] = useState('');
    const [detalleAlerta, setDetalleAlerta] = useState('');
    const [estadoAlerta, setEstadoAlerta] = useState(false);
    const [timeout, setTimeouts] = useState(3000);
    const inputRef = useRef<HTMLInputElement>(null); // Especifica que inputRef es una referencia a un elemento HTMLInputElement

    const [ean13, setEan13] = useState(null);
    const [ean14, setEan14] = useState(null);
    const [ean128, setEan128] = useState(null);



    // Al iniciar, se consulta el estado actual:
    // El estado actual se determina verificando si hay una sesión activa en el localStorage.
    // Si no hay una sesión activa en el localStorage, el estado se establece como vacío.
    // Si hay una sesión activa en el localStorage, el estado se establece como 'INICIAR'.

    // Al cambiar el estado a 'FINALIZAR', se deben validar los siguientes puntos:
    // 1. Verificar si existen etiquetas escaneadas en el localStorage.
    // 2. Si no existen etiquetas escaneadas, mostrar una alerta indicando que no se puede finalizar.
    // 3. Si existen etiquetas escaneadas, proceder a limpiar los estados, eliminar los datos del localStorage y cambiar el estado a vacío.

    // La función de escaneo de código debe obtener el primer código válido.
    // Si el código es válido, se verifica la existencia del ítem 'gestionesEtiqueta' en el localStorage.
    // Si no existe, se realiza la primera petición a la API para obtener los datos de la etiqueta. Estos datos se almacenan en 'gestionesEtiqueta' y se actualizan los campos necesarios en 'sessionData'.
    // Si 'gestionesEtiqueta' ya existe, se valida si el código ingresado coincide con uno de los EAN13 o EAN14 en 'sessionData'.
    // Si existe coincidencia, se almacena en 'gestionesEtiqueta' según su estructura normal.
    // Si el código no coincide con ninguno de los códigos en 'sessionData', se ingresa en 'gestionesEtiqueta' con el campo 'lote' marcado como INVALIDO.

    useEffect(() => {
        // Focaliza el input cuando el componente se monta
        if (inputRef.current) {
            inputRef.current.focus();
        }

        // Obtener la lista inicial de códigos escaneados cuando el componente se monta
        const sessionData = localStorage.getItem('sessionData');
        if (sessionData) {
            // Existe una sesión activa en el localStorage 
            const session = JSON.parse(sessionData);
            //console.log('Sesión activa encontrada:', session);
            setStatus(session.status); // Establecer el estado según la sesión encontrada
        } else {
            // No existe una sesión activa en el localStorage
            //console.log('No hay una sesión activa en el localStorage.');
            setStatus('');
        }

        // Limpiar el timer si el componente se desmonta o el código cambia
        return () => {
            if (searchTimer.current) {
                clearTimeout(searchTimer.current);
            }
        };

    }, []);
    useEffect(() => {
        fetchLatestScannedCodes();
        getSesion()
    }, []);

    // Método para manejar el clic del botón de estado
    const handleButtonClick = async () => {
        try {
            if (status === '') {
                //console.log("Entro a pedir crear una sesión");
                await generarSesion();
            } else if (status === 'INICIAR') {
                await cerrarSesion();
                clean();
            } else if (status === 'FINALIZAR') {
                await getStatus('');
                clean();
            }
        } catch (error) {
            console.error('Error al manejar el clic del botón:', error);
            // Manejar el error aquí (por ejemplo, mostrar un mensaje al usuario)
        }
    };



    const generarSesion = async () => {
        //console.log("Vamos a crear una sesión");
        setIsLoading(true);

        try {
            // Limpiar el localStorage
            localStorage.removeItem('sessionData');
            localStorage.removeItem('gestionesEtiqueta');

            // Generar una nueva sesión en el cliente
            const sessionData = {
                code: null,
                EAN13: null,
                EAN14: null,
                EAN128: null,
                lote: null,
                status: 'INICIAR',
                etiqueta: null,
                invalidas: 0,
                total_scans: 0,
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
                producto: null
            };

            // console.log("Se inició la sesión localmente", sessionData);

            // Almacenar datos de la sesión en localStorage
            localStorage.setItem('sessionData', JSON.stringify(sessionData));
            setStatus('INICIAR');
        } catch (error) {
            console.error('Error al crear la sesión:', error);
            // Manejar el error aquí (por ejemplo, mostrar un mensaje al usuario)
        } finally {
            setIsLoading(false);
        }

    };


    const cerrarSesion = async () => {

        setIsLoading(true);
        try {
            // Limpiar el estado de la aplicación
            clean();
        } catch (error) {
            console.error('Error al cerrar la sesión:', error);
            // Manejar el error aquí (por ejemplo, mostrar un mensaje al usuario)
        } finally {
            setIsLoading(false);
        }



    };

    // Tomo el valor del input antes que le de enter
    const ingresoEtiqueta = (e: React.ChangeEvent<HTMLInputElement>) => {
        const valor = e.target.value;
        if (valor.length >= 13 || valor.length <= 30) {
            setCode(valor);

        } else {
            setError('El código ingresado no cumple con el formato.');
            alertas();
        }


    };

    // Referencia para el timer de búsqueda
    const searchTimer = useRef<NodeJS.Timeout | null>(null); // Inicializa como NodeJS.Timeout | null

    // Cuando le realiza el enter al input de busqueda de etiqueta
    const accionScanner = async (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') {
            const scannedCode = inputRef.current?.value.trim(); // Obtener el código escaneado limpio

            if (scannedCode) {
                // Limpiar el timer de búsqueda previo
                if (searchTimer.current) {
                    clearTimeout(searchTimer.current);
                }

                setIsLoading(true);
                // Verificar si inputRef.current no es null antes de deshabilitarlo
                if (inputRef.current) {
                    inputRef.current.disabled = true; // Deshabilitar el input
                }


                try {
                    // Verificar el formato del código escaneado
                    const response = await axios.get(`/getEtiquetaFormato/${scannedCode}`);

                    const ean13 = response.data.EAN13;
                    const ean14 = response.data.EAN14;
                    const ean128 = response.data.EAN128;

                    setEan13(ean13);
                    setEan14(ean14);
                    setEan128(ean128);

                    // console.log("RESPUESTA DEL FORMATO: ", response.data, ean13, ean14, ean128);

                    // Verificar la existencia del ítem 'gestionesEtiqueta' en el localStorage
                    const storedGestionesEtiqueta = localStorage.getItem('gestionesEtiqueta');
                    const gestionesEtiqueta = storedGestionesEtiqueta ? JSON.parse(storedGestionesEtiqueta) : {};

                    // Obtener sessionData actual del localStorage
                    const storedSessionData = localStorage.getItem('sessionData');
                    const sessionData = storedSessionData ? JSON.parse(storedSessionData) : null;

                    if (storedGestionesEtiqueta) {
                        const timestamp = Date.now();

                        if ((ean13 && sessionData.EAN13 !== ean13) || (ean14 && sessionData.EAN14 !== ean14)) {
                            // La etiqueta es inválida
                            const ean13Invalido = ean13 && sessionData.EAN13 !== ean13 ? ean13 : null;
                            const ean14Invalido = ean14 && sessionData.EAN14 !== ean14 ? ean14 : null;
                            const ean128Invalido = ean128 && sessionData.EAN128 !== ean128 ? ean128 : null;

                            const invalidScannedCode = {
                                code: 'INVALIDO',
                                EAN13: ean13Invalido,
                                EAN14: ean14Invalido,
                                EAN128: ean128Invalido,
                                lote: 'INVALIDO',
                                producto: 'INVALIDO',
                                timestamp: timestamp
                            };

                            gestionesEtiqueta[timestamp] = [invalidScannedCode];
                            localStorage.setItem('gestionesEtiqueta', JSON.stringify(gestionesEtiqueta));
                            // Actualizar el índice en localStorage después de guardar
                            updateIndex(scannedCode, invalidScannedCode);
                            // console.log('Etiqueta inválida guardada: ', invalidScannedCode);
                        } else {
                            // La etiqueta es válida
                            if (sessionData.lote == 0 && ean128) {
                                sessionData.lote = ean128.substring(26);
                            }

                            const validScannedCode = {
                                code: sessionData.code,
                                EAN13: (ean13) ? ean13 : '',
                                EAN14: ean14,
                                EAN128: (ean128) ? ean128 : '',
                                lote: sessionData.lote,
                                producto: sessionData.producto,
                                timestamp: timestamp
                            };

                            gestionesEtiqueta[timestamp] = [validScannedCode];
                            localStorage.setItem('gestionesEtiqueta', JSON.stringify(gestionesEtiqueta));
                            // Actualizar el índice en localStorage después de guardar
                            updateIndex(scannedCode, validScannedCode);
                            // console.log('Etiqueta válida guardada: ', validScannedCode);
                        }
                    } else {
                        // console.log("El ítem 'gestionesEtiqueta' no existe en el localStorage.");

                        try {
                            const responseNuevo = await axios.get(`/crearNuevo/${ean13}/${ean14}/${ean128}`);

                            const datos_sessionData = responseNuevo.data.cabecera;
                            //  console.log("datos_sessionData", datos_sessionData)
                            let sessionData = JSON.parse(localStorage.getItem('sessionData') || '{}') as {
                                code: string;
                                EAN13: string;
                                EAN14: string;
                                EAN128: string;
                                lote: string;
                                etiqueta: string;
                                producto: string;
                            };

                            // Asignar valores asegurándose de manejar valores nulos
                            sessionData.code = datos_sessionData.code || '';
                            sessionData.EAN13 = datos_sessionData.EAN13 || '';
                            sessionData.EAN14 = datos_sessionData.EAN14 || '';
                            sessionData.EAN128 = datos_sessionData.EAN128 || '';
                            sessionData.lote = datos_sessionData.lote || '';
                            sessionData.etiqueta = datos_sessionData.etiqueta || '';
                            sessionData.producto = datos_sessionData.producto || '';


                            // Guardar el sessionData actualizado en el localStorage
                            localStorage.setItem('sessionData', JSON.stringify(sessionData));

                            const datos_detalleEtiqueta = responseNuevo.data.detalleEtiqueta;
                            const timestamp = Date.now();
                            const datos_gestionesEtiqueta = {
                                code: datos_detalleEtiqueta.code || '',
                                EAN13: datos_detalleEtiqueta.EAN13 || '',
                                EAN14: datos_detalleEtiqueta.EAN14 || '',
                                EAN128: datos_detalleEtiqueta.EAN128 || '',
                                lote: datos_detalleEtiqueta.lote || '',
                                producto: datos_detalleEtiqueta.producto || '',
                                timestamp: timestamp
                            };

                            gestionesEtiqueta[timestamp] = [datos_gestionesEtiqueta];
                            localStorage.setItem('gestionesEtiqueta', JSON.stringify(gestionesEtiqueta));
                            // Actualizar el índice en localStorage después de guardar
                            updateIndex(scannedCode, datos_gestionesEtiqueta);
                            /* console.log("Datos actualizados en el localStorage: ", {
                                sessionData: datos_sessionData,
                                gestionesEtiqueta: datos_gestionesEtiqueta
                            });

                            console.log(responseNuevo.data); */
                        } catch (error) {
                            console.error('Error al obtener datos nuevos: ', error);
                        }
                    }

                    fetchLatestScannedCodes();
                    getSesion();

                    setCode(''); // Limpiar el código escaneado después de la búsqueda exitosa
                    setIsLoading(false);
                } catch (err) {
                    setError('Código de etiqueta no válido');
                    setDetalleAlerta("Revise el código escaneado.");
                    setCode(''); // Limpiar el código escaneado en caso de error
                    alertas();
                    console.error(err);
                    setIsLoading(false);
                }

                // Verificar si inputRef.current no es null antes de habilitarlo y enfocarlo
                if (inputRef.current) {
                    inputRef.current.disabled = false; // Habilitar el input nuevamente
                    inputRef.current.focus(); // Enfocar el input
                }


            } else {
                if (inputRef.current) {
                    inputRef.current.focus(); // Enfocar el input
                }
                setIsLoading(false);
                setError('Código de etiqueta no válido');
                setDetalleAlerta("Revise el código escaneado.");
                setCode(''); // Limpiar el código escaneado en caso de error
            }
        }
    };

    // Función para actualizar el índice de códigos escaneados en localStorage
    const updateIndex = useCallback((code: string, scannedCode: ScannedCode) => {
        // Obtener el índice actual de códigos escaneados del localStorage
        const currentIndex = localStorage.getItem('scannedCodesIndex');
        let index: { [key: string]: number } = currentIndex ? JSON.parse(currentIndex) : {};

        // Actualizar el índice con el nuevo código escaneado
        index[code] = scannedCodes.length - 1; // Usar el índice del último código escaneado

        // Guardar el índice actualizado en localStorage
        localStorage.setItem('scannedCodesIndex', JSON.stringify(index));
    }, [scannedCodes]);



    // Método para limpiar el estado de la aplicación
    const clean = () => {
        setSesionEtiqueta(null);
        setCode('');
        setStatus('');
        setScannedCodes([]);
        setError('');
        localStorage.removeItem('sessionData');
        localStorage.removeItem('gestionesEtiqueta');
        setIsLoading(false);
    };

    const getStatus = useCallback((newStatus: string) => {
        setIsLoading(true);

        if (newStatus === 'INICIAR') {
            // generaba la nueva sesion local sotrage y la pasamos a sesion 
            setIsLoading(false);
            //console.log('INICIAR')
        } else if (newStatus === 'FINALIZAR') {
            //console.log("ESTA FINALIZANDO....")
            localStorage.removeItem('sessionData');
            localStorage.removeItem('gestionesEtiqueta');

        }
    }, [sessionId, scannedCodes.length]);



    //Consultar si sesion, si existe no consulta al controlador si no al storage
    const getSesion = async () => {
        try {
            // Consultar localStorage primero
            const storedSesionEtiqueta = localStorage.getItem('sessionData');

            if (storedSesionEtiqueta) {
                // Si existe en localStorage, usar esa información
                const sesionEtiqueta = JSON.parse(storedSesionEtiqueta);
                setSesionEtiqueta(sesionEtiqueta);
                setError('');
            } else {
                setIsLoading(false);

                setError('');
            }

            // Actualizar la lista de los últimos códigos escaneados

            setCode('');
        } catch (err) {
            // Manejar errores en la obtención de datos del producto
            setSesionEtiqueta(null);
            setError("No se consiguió sessionId");
            setDetalleAlerta("Reinicie el Navegador");
            alertas();
            console.error(err);
        }
    };

    const getEstadoEtiqueta = () => {
        const storedSessionData = localStorage.getItem('sessionData');

        if (storedSessionData) {
            const activeSession = JSON.parse(storedSessionData);
            //console.log('Sesión activa encontrada:', activeSession);

            if (activeSession.etiqueta === null) {
                //console.log('El campo etiqueta está null.');
                setStatusEtiqueta(false);
                return { ...activeSession, etiquetaIsNull: true };
            } else {
                //console.log('El campo etiqueta no está null.');
                setStatusEtiqueta(true);
                return { ...activeSession, etiquetaIsNull: false };
            }
        } else {
            console.log('No hay una sesión activa.');
            return null;
        }
    };


    const alertas = () => {
        setEstadoAlerta(true)
        setMensajeAlerta(error);
        setTimeout(() => {
            setEstadoAlerta(false)
        }, timeout);
    }


    // Método para obtener la lista de los últimos códigos escaneados
    const fetchLatestScannedCodes = async () => {
        try {
            // Obtener gestionesEtiqueta del localStorage
            const storedGestionesEtiqueta = localStorage.getItem('gestionesEtiqueta');
            if (!storedGestionesEtiqueta) {
                setScannedCodes([]);
            } else {
                const gestionesEtiqueta = storedGestionesEtiqueta ? JSON.parse(storedGestionesEtiqueta) : {};

                // Inicializar un array para almacenar los códigos escaneados
                const scannedCodes = [];


                // Recorrer las claves del objeto gestionesEtiqueta
                for (const timestamp in gestionesEtiqueta) {
                    if (gestionesEtiqueta.hasOwnProperty(timestamp)) {
                        // Obtener los códigos escaneados para cada timestamp y agregarlos al array
                        scannedCodes.push(...gestionesEtiqueta[timestamp]);
                    }
                }

                // Ordenar los códigos escaneados por timestamp de manera descendente
                scannedCodes.sort((a, b) => b.timestamp - a.timestamp);

                // Actualizar el estado con los códigos escaneados
                setScannedCodes(scannedCodes);

                // Verificar si hay códigos inválidos
                const hayCodigosInvalidos = scannedCodes.some(code => code.EAN13 === 'INVALIDO' || code.EAN128 === 'INVALIDO');
                if (hayCodigosInvalidos) {
                    // Mostrar alerta o manejar de acuerdo a tus requerimientos
                    setError("Código de etiqueta no válido");
                    setDetalleAlerta("Revise el código escaneado.");
                    alertas();
                    setEstadoAlerta(true);
                }

                // Si hay códigos escaneados, actualizar el estado del componente
                if (scannedCodes.length > 0) {
                    //console.log('VALIDAR SI HAY MAS DE 0', scannedCodes[0]);
                    setStatus('INICIAR');
                    //console.log("estado finalizar..........")
                } else {
                    setStatus('');
                    // console.log('VALIDAR SI HAY MENOS DE ', scannedCodes[0]);
                }
            }
        } catch (err) {
            // Manejar errores en la obtención de los códigos escaneados
            console.error('Error fetching latest scanned codes:', err);
        }
    };





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
                                    onChange={ingresoEtiqueta}
                                    onKeyDown={accionScanner}
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
                    <div className="grid mt-4 content-normal" style={{ gridTemplateColumns: "0.8fr 0.6fr 3fr" }}>
                        <div className="col-span-1">
                            <ScrollArea className="h-[490px] max-w-[590px] rounded-md border p-4 bg-slate-50 overflow-auto">
                                <h4 className="mb-4 text-sm font-medium leading-none">  <CardDescription className='text-[#322b9d]'>EAN 128</CardDescription></h4>
                                {scannedCodes.map((code, index) => (
                                    code.EAN128 && code.EAN128 !== 'null' && (
                                        <div key={index} className="md:text-3xl">
                                            <span className="text-xs text-gray-300">{code.EAN128 ? scannedCodes.length - index : ''}</span>
                                            {code.lote === 'INVALIDO' ? (
                                                <span className="text-red-500 italic">{code.EAN128 ? code.EAN128.slice(0, 30) : ''}</span>
                                            ) : (
                                                <span className="text-green-700">{code.EAN128 ? code.EAN128.slice(0, 30) : ''}</span>
                                            )}
                                        </div>
                                    )
                                ))}
                            </ScrollArea>

                        </div>
                        <div className="col-span-1  px-2">
                            <ScrollArea className="h-[490px] max-w-[260px] rounded-md border p-4 bg-slate-50 overflow-auto">
                                <h4 className="mb-4 text-sm font-medium leading-none"> <CardDescription className='text-[#322b9d]'>EAN 13</CardDescription>  </h4>
                                {scannedCodes.length > 0 ? (
                                    scannedCodes.map((code, index) => (
                                        code.EAN13 && code.EAN13 !== 'null' && (
                                            <div key={index} className="md:text-3xl">
                                                <span className="text-xs text-gray-300">{scannedCodes.length - index}</span>
                                                {code.lote === 'INVALIDO' ? (
                                                    <span className="text-red-500 italic">
                                                        {code.EAN13.slice(0, 13)}
                                                    </span>
                                                ) : (
                                                    <span className="text-green-700">
                                                        {code.EAN13.slice(0, 13)}
                                                    </span>
                                                )}
                                            </div>
                                        )
                                    ))
                                ) : (
                                    <div className="text-lg text-red-500">Aún no hay productos escaneados. Por favor, escanee uno.</div>
                                )}

                            </ScrollArea>
                        </div>
                        <div className="col-span-1">
                            {sesionEtiqueta && (
                                <Card>
                                    <CardHeader>
                                        <CardDescription className="mb-4 text-sm font-medium leading-none text-[#322b9d]">PRODUCTO ENCONTRADO</CardDescription>
                                        <CardDescription>
                                            <b className="md:text-2xl text-[#322b9d]">Codigo:</b> <strong className="md:text-4xl text-[#322b9d]">{sesionEtiqueta.code}</strong>
                                        </CardDescription>
                                        <Separator />
                                        <CardTitle className="md:text-3xl flex "> {sesionEtiqueta.producto}</CardTitle>
                                    </CardHeader>
                                    {sesionEtiqueta.lote != 0 && (
                                        <>
                                            <Separator />
                                            <CardContent className="grid gap-4">
                                                <CardDescription>
                                                    <b className="md:text-2xl text-[#322b9d]">N° Lote:</b>
                                                    <strong className="md:text-4xl text-[#322b9d]">{sesionEtiqueta.lote}</strong>
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
