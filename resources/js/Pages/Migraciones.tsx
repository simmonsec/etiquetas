import { Badge } from '@/shadcn/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/shadcn/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/shadcn/ui/table";
import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from "@/shadcn/ui/button"
import Countdown from '../Components/CuentaRegresiva'; // Importamos el componente Countdown

import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/shadcn/ui/sheet"
import {
    Home,
} from "lucide-react"
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/shadcn/ui/tooltip"

import {
    Breadcrumb,
  
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from "@/shadcn/ui/breadcrumb"
 
interface Evento {
    descripcion: string;
    e_estado: 'A' | 'I'; // 'A' para Activo, 'I' para Inactivo
    e_secuencia: number;
    e_ultima: string; // Fecha en formato ISO o string legible
    e_proxima?: string; // Fecha opcional
    e_resultado: 'Finalizado' | 'Error' | 'Ejecutándose...' | 'Pendiente';
    cant_encontrados?: number; // Opcional, 0 o más
    cant_insertados?: number; // Opcional, 0 o más
    tiempo_ejecucion: number; // Tiempo en segundos
    e_frecuencia: number;
    e_type: 'QUERY' | 'PROCEDURE' | 'TRIGGER';
    i_campos_deseados: string;
}


const Migraciones = () => {
    const [tareas, setTareas] = useState<Evento[]>([]);
    const [currentTime, setCurrentTime] = useState(new Date());
 
    const [subTareas, setSubTareas] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    // Formatear fecha y hora
    const formatDateTime = (dateString) => {
        if (!dateString) return "N/A";
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('es-ES', {
            dateStyle: 'short',
            timeStyle: 'short',
        }).format(date);
    };

    useEffect(() => {
        const interval = setInterval(() => {
            getTareas();
        }, 1000);

        return () => clearInterval(interval);
    }, []);

    async function getTareas() {
        try {
            const response = await fetch('/api/migraciones/tareas');
            const data = await response.json();
            setTareas(data);
            //console.log(data)
        } catch (error) {
            console.error('Error al obtener el Indicador 2:', error);
        }
    }

    const getSubTareas = async (id) => {
        setLoading(true);
        try {
            const response = await fetch(`/api/migraciones/subTareas/${id}`);
            const data = await response.json();
            setSubTareas(data);
        } catch (error) {
            setError(error.message);
        } finally {
            setLoading(false);
        }
    };
    // Actualizar reloj cada segundo
    useEffect(() => {
        const interval = setInterval(() => {
            setCurrentTime(new Date());
            getTareas();
        }, 1000);

        return () => clearInterval(interval);
    }, []);


    return (
        <>
            <Head title="MIGRACIONES MBA" />
            <Card className="shadow-lg rounded-lg border border-gray-200">
                <CardHeader className="bg-gradient-to-r from-blue-500 to-teal-500 px-7 py-4 rounded-t-lg shadow-md">
                    {/* Fila con título a la izquierda y reloj a la derecha */}
                    <div className="flex justify-between items-center">
                        {/* Título alineado a la izquierda */}
                        <CardTitle className="text-2xl font-bold text-white">
                            Gestión de Eventos y Subprocesos
                        </CardTitle>

                        {/* Reloj alineado a la derecha */}
                        <div className="text-lg font-semibold text-white">
                            {currentTime.toLocaleTimeString()}
                        </div>
                    </div>

                    {/* Descripción debajo del título */}
                    <CardDescription className="text-sm text-white/80 mt-2">
                        Actualización de parámetros en tiempo real para el monitoreo y control de eventos.
                    </CardDescription>

                    {/* Indicadores de tipo de evento */}
                    <div className="flex items-center space-x-4 mt-4 text-sm">
                        <div className="flex items-center">
                            <div className="w-3 h-3 rounded-full bg-blue-300 mr-2" />
                            <p className="text-white">Evento Principal</p>
                        </div>
                        <div className="flex items-center">
                            <div className="w-3 h-3 rounded-full bg-orange-500 mr-2" />
                            <p className="text-white">Subproceso Asociado</p>
                        </div>
                    </div>

                    {/* Breadcrumbs */}
                    <Breadcrumb className="mt-4">
                        <BreadcrumbList>
                            <BreadcrumbItem className="text-white">
                                <BreadcrumbLink href="/" >
                                    <Link
                                        href="/dashboard"
                                        className="flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:text-foreground md:h-8 md:w-8"
                                    >
                                        <Home className="h-5 w-5" fill="white" />
                                    </Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator className="text-white"/>
                            <BreadcrumbItem>
                                <BreadcrumbLink href="#" className="text-white">Migraciones</BreadcrumbLink>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </CardHeader>



                <CardContent className="p-4">
                    <Table className="w-full border-collapse border border-gray-300 text-sm rounded-lg overflow-hidden">
                        <TableHeader className="bg-gray-100 text-gray-900">
                            <TableRow>
                                <TableHead className="py-3 px-4 text-left font-semibold text-gray-800">Descripción</TableHead>
                                <TableHead className="hidden sm:table-cell py-3 px-4 text-center text-gray-800">Secuencia</TableHead>
                                <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Última Ejecución</TableHead>
                                <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Frecuencia</TableHead>
                                <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Próxima Ejecución</TableHead>
                                <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Resultado Ejecución</TableHead>
                                <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Registros</TableHead>
                                <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Tiempo Ejecución</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tareas.map((evento, index) => {
                                const filaResaltada = evento.e_resultado === 'Ejecutándose...' ? 'bg-yellow-200' : '';

                                return (
                                    <TableRow
                                        key={index}
                                        className={`${index % 2 === 0 ? "bg-white" : "bg-gray-50"
                                            } ${filaResaltada} hover:bg-blue-100 transition-colors duration-200 border-t border-b border-gray-200`}
                                    >
                                        <TableCell className="py-3 px-4">
                                            <div className="flex items-center">
                                                <div
                                                    className={`w-3 h-3 rounded-full mr-2 ${evento.e_secuencia == 0 ? 'bg-orange-500' : 'bg-blue-500'}`}
                                                />
                                                <p className="font-medium text-gray-800">
                                                    {evento.descripcion}
                                                </p>
                                            </div>

                                            <div className="flex items-center space-x-3">
                                                <div className="mt-2 flex space-x-2">
                                                    <Badge variant="secondary" className="bg-gray-300 text-gray-700">{evento.e_type}</Badge>
                                                    {evento.e_estado === 'A' ? (
                                                        <Badge className="bg-green-100 text-green-700 border border-green-200 px-2 py-1 rounded-md">
                                                            Activo
                                                        </Badge>
                                                    ) : (
                                                        <Badge className="bg-red-100 text-red-700 border border-red-200 px-2 py-1 rounded-md">
                                                            Inactivo
                                                        </Badge>
                                                    )}
                                                </div>
                                                {evento.subprocesos_count > 0 ? (
                                                    <Sheet>
                                                        <SheetTrigger asChild>
                                                            <Badge
                                                                variant="outline"
                                                                onClick={() => getSubTareas(evento.id)}
                                                                className="bg-teal-600 text-white hover:bg-teal-100 border border-green-200 px-2 py-1 rounded-md mt-2"
                                                            >
                                                                {evento.subprocesos_count} {evento.subprocesos_count > 1 ? 'Subprocesos' : 'Subproceso'}
                                                            </Badge>
                                                        </SheetTrigger>
                                                        <SheetContent side="top" className="max-w-4xl mx-auto px-4 py-6 sm:px-6">
                                                            <SheetHeader>
                                                                <SheetTitle className="text-xl font-semibold text-gray-800">Descripción del Proceso: {evento.descripcion}</SheetTitle>
                                                                <SheetDescription className="text-gray-500 mt-2">
                                                                    {evento.subprocesos_count > 0
                                                                        ? evento.c_nombreTabla ? `Tabla a Proceso: ${evento.c_nombreTabla}` : ''
                                                                        : 'No hay subprocesos asociados.'}
                                                                </SheetDescription>
                                                            </SheetHeader>

                                                            <div className="mt-6">
                                                                <h3 className="text-lg font-semibold text-gray-800 mb-4">Subprocesos</h3>

                                                                {/* Aquí mostramos los subparámetros en una tabla */}
                                                                {subTareas.length > 0 ? (
                                                                    <div className="overflow-x-auto max-h-96">
                                                                        <Table className="min-w-full border-collapse border border-gray-300 text-sm rounded-lg">
                                                                            <TableHeader className="bg-gray-100 text-gray-900">
                                                                                <TableRow>
                                                                                    <TableHead className="py-3 px-4 text-left font-semibold text-gray-800">Descripción</TableHead>
                                                                                    <TableHead className="hidden sm:table-cell py-3 px-4 text-center text-gray-800">Secuencia</TableHead>
                                                                                    <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Última</TableHead>
                                                                                    <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Frecuencia</TableHead>
                                                                                    <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Próxima</TableHead>
                                                                                    <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Resultado</TableHead>
                                                                                    <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Registros</TableHead>
                                                                                    <TableHead className="hidden md:table-cell py-3 px-4 text-center text-gray-800">Tiempo</TableHead>
                                                                                </TableRow>
                                                                            </TableHeader>
                                                                            <TableBody>
                                                                                {subTareas.map((subtarea) => (
                                                                                    <TableRow key={subtarea.id}>
                                                                                        <TableCell className="py-3 px-4">{subtarea.descripcion}</TableCell>
                                                                                        <TableCell className="py-3 px-4">{subtarea.e_secuencia}</TableCell>
                                                                                        <TableCell className="py-3 px-4">{formatDateTime(subtarea.e_ultima)}</TableCell>
                                                                                        <TableCell className="py-3 px-4">{evento.e_frecuencia} {evento.e_frecuencia > 1 ? 'minutos' : 'minuto'}</TableCell>
                                                                                        <TableCell className="py-3 px-4">{formatDateTime(subtarea.e_proxima)}</TableCell>
                                                                                        <TableCell className="hidden md:table-cell text-center py-3 px-4">
                                                                                            {subtarea.e_resultado === 'Finalizado' ? (
                                                                                                <Badge className="bg-green-100 text-green-700 border border-green-200 px-2 py-1 rounded-md">Finalizado</Badge>
                                                                                            ) : subtarea.e_resultado === 'Error' ? (
                                                                                                <Badge className="bg-red-100 text-red-700 border border-red-200 px-2 py-1 rounded-md">Error</Badge>
                                                                                            ) : subtarea.e_resultado === 'Ejecutándose...' ? (
                                                                                                <Badge className="bg-yellow-100 text-yellow-700 border border-yellow-200 px-2 py-1 rounded-md">Ejecutándose...</Badge>
                                                                                            ) : (
                                                                                                <Badge className="bg-gray-100 text-gray-700 border border-gray-200 px-2 py-1 rounded-md">Pendiente</Badge>
                                                                                            )}
                                                                                        </TableCell>
                                                                                        <TableCell className="py-3 px-4">{subtarea.i_comando}</TableCell>
                                                                                        <TableCell className="py-3 px-4">{subtarea.tiempo_ejecucion}</TableCell>
                                                                                    </TableRow>
                                                                                ))}
                                                                            </TableBody>
                                                                        </Table>
                                                                    </div>
                                                                ) : (
                                                                    <p className="text-gray-500">No hay subparámetros disponibles.</p>
                                                                )}
                                                            </div>
                                                        </SheetContent>
                                                    </Sheet>
                                                ) : (
                                                    <p className="text-gray-500"></p>
                                                )}

                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell text-center py-3 px-4">
                                            {evento.e_secuencia == 0 ? evento.subp_secuencia : evento.e_secuencia}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell text-center py-3 px-4">
                                            {formatDateTime(evento.e_ultima)}
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell text-center py-3 px-4">{evento.e_frecuencia} {evento.e_frecuencia > 1 ? ' minutos' : 'minuto'}</TableCell>
                                        <TableCell className="hidden md:table-cell text-center py-3 px-4">
                                            {evento.e_proxima ? formatDateTime(evento.e_proxima) : "No programada"}
                                            <p><small><Badge variant="outline"><Countdown targetDate={evento.e_proxima} /></Badge></small></p>
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell text-center py-3 px-4">
                                            {evento.e_resultado === 'Finalizado' ? (
                                                <Badge className="bg-green-100 text-green-700 border border-green-200 px-2 py-1 rounded-md">Finalizado</Badge>
                                            ) : evento.e_resultado === 'Error' ? (
                                                <Badge className="bg-red-100 text-red-700 border border-red-200 px-2 py-1 rounded-md">Error</Badge>
                                            ) : evento.e_resultado === 'Ejecutándose...' ? (
                                                <Badge className="bg-yellow-100 text-yellow-700 border border-yellow-200 px-2 py-1 rounded-md">Ejecutándose...</Badge>
                                            ) : (
                                                <Badge className="bg-gray-100 text-gray-700 border border-gray-200 px-2 py-1 rounded-md">Pendiente</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="hidden md:table-cell text-center py-3 px-4">
                                            {(evento.cant_encontrados > 0 || evento.cant_insertados > 0) ? (
                                                <div className="flex justify-center space-x-4">
                                                    {/* Cantidad de Registros Encontrados */}
                                                    {evento.cant_encontrados > 0 && (
                                                        <TooltipProvider>
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="outline" className="text-blue-500 hover:bg-blue-50">
                                                                        {evento.cant_encontrados}
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>Cantidad de registros encontrados</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        </TooltipProvider>
                                                    )}

                                                    {/* Cantidad de Registros Insertados */}
                                                    {evento.cant_insertados > 0 && (
                                                        <TooltipProvider>
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="outline" className="text-green-500 hover:bg-green-50">
                                                                        {evento.cant_insertados}
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>
                                                                    <p>Cantidad de registros insertados</p>
                                                                </TooltipContent>
                                                            </Tooltip>
                                                        </TooltipProvider>
                                                    )}
                                                </div>
                                            ) : null}
                                        </TableCell>

                                        <TableCell className="hidden md:table-cell text-center py-3 px-4">{evento.tiempo_ejecucion}</TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

        </>
    );
};

export default Migraciones;
