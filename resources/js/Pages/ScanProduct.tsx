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
    const [sesionEtiqueta, setSesionEtiqueta] = useState(null);
    const [error, setError] = useState('');
    const [scannedCodes, setScannedCodes] = useState([]);
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
            console.log('Sesión activa encontrada:', session);
            setStatus(session.status); // Establecer el estado según la sesión encontrada
        } else {
            // No existe una sesión activa en el localStorage
            console.log('No hay una sesión activa en el localStorage.');
            setStatus('');
        }

        // Limpiar el timer si el componente se desmonta o el código cambia
        return () => {
            if (searchTimer.current) {
                clearTimeout(searchTimer.current);
            }
        };
    }, []);



    // Método para manejar el clic del botón de estado
    const handleButtonClick = async () => {
        try {
            if (status === '') {
                console.log("Entro a pedir crear una sesión");
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
        console.log("Vamos a crear una sesión");
        setIsLoading(true);

        try {
            // Limpiar el localStorage
            localStorage.removeItem('sessionData');
            localStorage.removeItem('gestionesEtiqueta');

            // Generar una nueva sesión en el cliente
            const sessionData = {
                id: Date.now(),
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

            setSessionId(sessionData.id);
            console.log("Se inició la sesión localmente", sessionData);

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


    // Tomo el valor del input antes que le de enter
    const ingresoEtiqueta = (e) => {
        setCode(e.target.value);

    };

    // Referencia para el timer de búsqueda
    const searchTimer = useRef<NodeJS.Timeout | null>(null); // Inicializa como NodeJS.Timeout | null

    // Cuando le realiza el enter al input de busqueda de etiqueta
    const accionScanner = async (e) => {
        if (e.key === 'Enter') {
            const scannedCode = e.target.value.trim(); // Obtener el código escaneado limpio

            if (scannedCode) {
                // Limpiar el timer de búsqueda previo
                if (searchTimer.current) {
                    clearTimeout(searchTimer.current);
                }

                setIsLoading(true);
                inputRef.current.disabled = true; // Deshabilitar el input

                try {
                    // VERIFICAR SI EL CODIGO INGRESADO ES VALIDO, Y TIENE UN FORMATO REQUERIDO EAN13 Y EAN14 (CON EL EAN128 INGRESADO), CON EL CONTROLADOR
                    const response = await axios.get(`/getEtiquetaFormato/${scannedCode}`);

                    const ean13 = response.data.EAN13;
                    const ean14 = response.data.EAN14;
                    const ean128 = response.data.EAN128;

                    setEan13(ean13);
                    setEan14(ean14);
                    setEan128(ean128);

                    console.log("RESPUESTA DEL FORMATO: ", response.data, ean13, ean14, ean128);


                    // Verificar la existencia del ítem 'gestionesEtiqueta' en el localStorage
                    const storedGestionesEtiqueta = localStorage.getItem('gestionesEtiqueta');
                    if (storedGestionesEtiqueta) {
                        console.log("El ítem 'gestionesEtiqueta' existe en el localStorage.");

                        // Obtener sessionData actual del localStorage
                        const storedSessionData = localStorage.getItem('sessionData');
                        const sessionData = storedSessionData ? JSON.parse(storedSessionData) : null;

                        // Verificar si la etiqueta escaneada no es la misma que la de la sesión activa
                        if ((ean13 && sessionData.EAN13 !== ean13) || (ean14 && sessionData.EAN14 !== ean14)) {
                            console.log('La etiqueta ingresada no es la misma que se escaneó al inicio para la sesión activa, y la guarda como inválida');

                            const ean13Invalido = ean13 && sessionData.EAN13 !== ean13 ? ean13 : null;
                            const ean14Invalido = ean14 && sessionData.EAN14 !== ean14 ? ean14 : null;
                            const ean128Invalido = ean128 && sessionData.EAN128 !== ean128 ? ean128 : null;

                            // Crear y guardar datos inválidos
                            const invalidScannedCode = {
                                id: sessionData.id,
                                scan_session_id: sessionData.id,
                                code: 'INVALIDO',
                                EAN13: ean13Invalido,
                                EAN14: ean14Invalido,
                                EAN128: ean128Invalido,
                                lote: 'INVALIDO',
                                producto: 'INVALIDO'
                            };

                            // Parsear gestionesEtiqueta y agregar el nuevo código inválido
                            const gestionesEtiqueta = JSON.parse(storedGestionesEtiqueta);
                            gestionesEtiqueta[sessionData.id] = invalidScannedCode; // Usando sessionData.id como clave
                            localStorage.setItem('gestionesEtiqueta', JSON.stringify(gestionesEtiqueta));

                            console.log('Etiqueta inválida guardada: ', invalidScannedCode);
                        } else {
                            // Si la etiqueta es la misma que la de la sesión activa, actualizar y guardar el registro
                            if (sessionData.lote == 0 && ean128) {
                                sessionData.lote = ean128.substring(26);
                            }

                            const validScannedCode = {
                                id: sessionData.id,
                                scan_session_id: sessionData.id,
                                code: sessionData.code,
                                EAN13: ean13,
                                EAN14: ean14,
                                EAN128: ean128,
                                lote: sessionData.lote,
                                producto: sessionData.producto
                            };

                            // Parsear gestionesEtiqueta y agregar el nuevo código válido
                            const gestionesEtiqueta = JSON.parse(storedGestionesEtiqueta);
                            gestionesEtiqueta[sessionData.id] = validScannedCode; // Usando sessionData.id como clave
                            localStorage.setItem('gestionesEtiqueta', JSON.stringify(gestionesEtiqueta));

                            console.log('Etiqueta válida guardada: ', validScannedCode);
                        }
                    } else {
                        // Si 'gestionesEtiqueta' no existe en el localStorage, vamos a generar el primero.
                        console.log("El ítem 'gestionesEtiqueta' no existe en el localStorage.");
                        console.log(ean13, ean14, ean128);

                        try {
                            const responseNuevo = await axios.get(`/crearNuevo/${ean13}/${ean14}/${ean128}`);

                            // Datos para actualizar sessionData en el localStorage
                            const datos_sessionData = responseNuevo.data.cabecera; // Obtenido de responseNuevo.data.cabecera

                            // Obtener sessionData actual del localStorage
                            let sessionData = JSON.parse(localStorage.getItem('sessionData'));

                            // Actualizar los campos necesarios en sessionData
                            sessionData.code = datos_sessionData.code;
                            sessionData.EAN13 = datos_sessionData.EAN13;
                            sessionData.EAN14 = datos_sessionData.EAN14;
                            sessionData.EAN128 = datos_sessionData.EAN128;
                            sessionData.lote = datos_sessionData.lote;
                            sessionData.etiqueta = datos_sessionData.etiqueta;
                            sessionData.producto = datos_sessionData.producto;

                            // Guardar el sessionData actualizado en el localStorage
                            localStorage.setItem('sessionData', JSON.stringify(sessionData));

                            // Datos para crear gestionesEtiqueta en el localStorage
                            const datos_gestionesEtiqueta = {
                                [sessionData.id]: {
                                    id: sessionData.id,
                                    scan_session_id: sessionData.id,
                                    code: sessionData.code,
                                    EAN13: sessionData.EAN13,
                                    EAN14: sessionData.EAN14,
                                    EAN128: sessionData.EAN128,
                                    lote: sessionData.lote,
                                    producto: sessionData.producto
                                }
                            };

                            // Crear gestionesEtiqueta en el localStorage
                            localStorage.setItem('gestionesEtiqueta', JSON.stringify(datos_gestionesEtiqueta));

                            console.log("Datos actualizados en el localStorage: ", {
                                sessionData: datos_sessionData,
                                gestionesEtiqueta: datos_gestionesEtiqueta
                            });

                            console.log(responseNuevo.data);
                            // Puedes realizar acciones adicionales después de crear gestionesEtiqueta en localStorage.
                        } catch (error) {
                            console.error('Error al obtener datos nuevos: ', error);
                            // Manejo de errores según tus requerimientos.
                        }
                    }



                    fetchLatestScannedCodes();

                    setCode(''); // Limpiar el código escaneado después de la búsqueda exitosa
                    setIsLoading(false);
                } catch (err) {
                    // Manejar errores en la obtención de datos del ETIQUETA
                    setError('Código de etiqueta no válido');
                    setDetalleAlerta("Revise el código escaneado.");
                    setCode(''); // Limpiar el código escaneado en caso de error
                    alertas();
                    console.error(err);
                    setIsLoading(false);
                }

                inputRef.current.disabled = false; // Habilitar el input nuevamente
                inputRef.current.focus(); // Enfocar el input
            } else {
                inputRef.current.focus(); // Enfocar el input
                setIsLoading(false);
                setError('Código de etiqueta no válido');
                setDetalleAlerta("Revise el código escaneado.");
                setCode(''); // Limpiar el código escaneado en caso de error
            }
        }
    };





    const cerrarSesion = async () => {
        console.log("Vamos a cerrar la sesión");
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


    // Método para limpiar el estado de la aplicación
    const clean = () => {
        setSesionEtiqueta(null);
        setCode('');
        setStatus('');
        setScannedCodes([]);
        setError('');
        localStorage.removeItem('sessionData');
        localStorage.removeItem('scannedCodes');
    };

    const getStatus = useCallback((newStatus) => {
        setIsLoading(true);

        if (newStatus === 'INICIAR') {

            // generaba la nueva sesion local sotrage y la pasamos a sesion

            setIsLoading(false);

        } else if (newStatus === 'FINALIZAR') {
            const storedSessionData = localStorage.getItem('sessionData');
            const sessionData = storedSessionData ? JSON.parse(storedSessionData) : null;

            if (sessionData && scannedCodes.length > 0) {
                const dataToSend = {
                    sessionId: sessionData.id,
                    total_scans: scannedCodes.length,
                    scannedCodes: scannedCodes // Asegúrate de que `scannedCodes` esté almacenando los códigos escaneados
                };
                console.log('Se procede a enviar la data del local storage al controlador porque se está finalizando la sesión', dataToSend);

                axios.post(`/scan-session/end/${sessionData.id}`, dataToSend)
                    .then(() => {
                        setSessionId(null);
                        setStatus('');
                        // Limpiar datos de la sesión del localStorage
                        localStorage.removeItem('sessionData');
                        localStorage.removeItem('scannedCodes');
                    })
                    .catch(error => {
                        console.error('Error al finalizar la sesión de escaneo:', error);
                    })
                    .finally(() => setIsLoading(false));
            } else if (sessionData && scannedCodes.length < 1) {
                setEstadoAlerta(true);
                setError('No se puede finalizar');
                setDetalleAlerta("Debes al menos escanear una etiqueta.");
                console.error('No se puede finalizar porque no hay etiquetas registradas.');

                setIsLoading(false);
                setTimeout(() => {
                    setEstadoAlerta(false);
                }, timeout);
            } else {
                setEstadoAlerta(true);
                console.error('No se puede finalizar una sesión sin un ID de sesión válido.');
                setError('No se puede finalizar');
                setDetalleAlerta("Una sesión sin un ID de sesión válido.");
                setIsLoading(false);
                setTimeout(() => {
                    setEstadoAlerta(false);
                }, timeout);
            }
        }
    }, [sessionId, scannedCodes.length]);



    //Consultar si sesion, si existe no consulta al controlador si no al storage
    const getSesion = async () => {
        try {
            // Consultar localStorage primero
            const storedSesionEtiqueta = localStorage.getItem('sesionEtiqueta');

            if (storedSesionEtiqueta) {
                // Si existe en localStorage, usar esa información
                const sesionEtiqueta = JSON.parse(storedSesionEtiqueta);
                setSesionEtiqueta(sesionEtiqueta);
                setError('');
            } else {
                setIsLoading(true);
                // Si no existe en localStorage, debe crearla
                //const response = await axios.get(`/getSesion/`);
                // Limpiar el localStorage
                localStorage.removeItem('sessionData');
                localStorage.removeItem('scannedCodes');

                // Generar una nueva sesión en el cliente
                const sessionData = {
                    id: Date.now(), // Generar un ID único basado en la fecha actual
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

                setSessionId(sessionData.id);
                console.log("Se inició la sesión localmente", sessionData);

                // Almacenar datos de la sesión en localStorage
                localStorage.setItem('sessionData', JSON.stringify(sessionData));

                setIsLoading(false);

                const storedSessionData = localStorage.getItem('sessionData');
                const sessionDataact = storedSessionData ? JSON.parse(storedSessionData) : null;
                const sesionEtiqueta = sessionDataact;

                // Actualizar el estado del producto y limpiar cualquier error previo
                setSesionEtiqueta(sesionEtiqueta);
                setError('');
            }

            // Actualizar la lista de los últimos códigos escaneados
            fetchLatestScannedCodes();
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
            // Solicitar la lista de los últimos códigos escaneados desde el servidor
            const response = await axios.get('/api/scanned-codes/latest');


            // Verificar si hay códigos inválidos en la respuesta
            if (response.data.EAN13INVALIDO || response.data.EAN128INVALIDO) {
                // Mostrar alerta o manejar de acuerdo a tus requerimientos

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
