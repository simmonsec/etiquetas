
import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import Image from 'next/image'; // Para Next.js
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/shadcn/ui/tooltip"
import {
    ChevronLeft,
    ChevronRight,
    Copy,
    CreditCard,
    File,
    Home,
    LineChart,
    ListFilter,
    MoreVertical,
    Package,
    Package2,
    PanelLeft,
    RefreshCw,
    Search,
    Settings,
    ShoppingCart,
    Truck,
    Users2,
} from "lucide-react"

import { Badge } from "@/shadcn/ui/badge"
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from "@/shadcn/ui/breadcrumb"
import { Button } from "@/shadcn/ui/button"
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/shadcn/ui/card"
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/shadcn/ui/dropdown-menu"
import { Input } from "@/shadcn/ui/input"
import {
    Pagination,
    PaginationContent,
    PaginationItem,
} from "@/shadcn/ui/pagination"
import { Progress } from "@/shadcn/ui/progress"
import { Separator } from "@/shadcn/ui/separator"
import { Sheet, SheetContent, SheetTrigger } from "@/shadcn/ui/sheet"
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/shadcn/ui/table"
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from "@/shadcn/ui/tabs"


import * as XLSX from 'xlsx';

const Dashboard = () => {

    const [indicador3, setIndicador3] = useState([]);
    const [indicador1, setIndicador1] = useState([]);
    const [indicador2, setIndicador2] = useState([]);

    useEffect(() => {
        // Obtener los datos al montar el componente
        getIndicador3();
        getIndicador1();
        getIndicador2();
    }, []);

    async function getIndicador2() {
        try {
            const response = await fetch('/api/eventos/reporte2');
            const data = await response.json();
            console.log(data);
            setIndicador2(data)
            console.log(Array.isArray(indicador2));
        } catch (error) {
            console.error('Error al obtener el Indicador 2:', error);
            return { value: 0, percentage: 0 };
        }
    }

    async function getIndicador1() {
        try {
            const response = await fetch('/api/eventos/reporte1');
            const data = await response.json();
            setIndicador1(data)
            return data;
        } catch (error) {
            console.error('Error al obtener el Indicador 1:', error);
            return { value: 0, percentage: 0 };
        }
    }

    async function getIndicador3() {
        try {
            const response = await fetch('/api/eventos/reporte3');
            const data = await response.json();
            setIndicador3(data)
            return data;
        } catch (error) {
            console.error('Error al obtener el reporte de eventos de hoy:', error);
            return [];
        }
    }

    function exportReport() {
        // Obtén los datos que deseas exportar
        getIndicador3().then((data) => {
            // Configura la hoja de cálculo
            const ws = XLSX.utils.json_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "EventosHoy");

            // Exporta el archivo
            XLSX.writeFile(wb, "ReporteEventosHoy.xlsx");
        });
    }

    return (
        <TooltipProvider>
            <Head title="Reportes Eventos" />
            <div className="flex min-h-screen w-full flex-col bg-muted/40">
                <aside className="fixed inset-y-0 left-0 z-10 hidden w-14 flex-col border-r bg-background sm:flex">
                    <nav className="flex flex-col items-center gap-4 px-2 sm:py-5">
                        {/* Enlace de Eventos con icono y tooltip */}
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Link
                                    href="/dashboard"
                                    className="group flex h-9 w-9 items-center justify-center gap-2 rounded-full bg-primary text-lg font-semibold text-primary-foreground md:h-8 md:w-8 md:text-base"
                                >
                                    <Home className="h-5 w-5 transition-all group-hover:scale-110" />
                                    <span className="sr-only">Inicio</span>
                                </Link>
                            </TooltipTrigger>
                            <TooltipContent side="right">Inicio</TooltipContent>
                        </Tooltip>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Link
                                    href="/"
                                    className="group flex h-9 w-9 shrink-0 items-center justify-center gap-2 rounded-full bg-primary text-lg font-semibold text-primary-foreground md:h-8 md:w-8 md:text-base"
                                >
                                    <Package2 className="h-4 w-4 transition-all group-hover:scale-110" />
                                    <span className="sr-only">Etiquetas</span>
                                </Link>
                            </TooltipTrigger>
                            <TooltipContent side="right">Etiquetas</TooltipContent>
                        </Tooltip>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Link
                                    href="/migraciones"
                                    className="group flex h-9 w-9 items-center justify-center gap-2 rounded-full bg-primary text-lg font-semibold text-primary-foreground md:h-8 md:w-8 md:text-base"
                                >
                                    <RefreshCw className="h-5 w-5 transition-all group-hover:scale-110" />
                                    <span className="sr-only">Migraciones</span>
                                </Link>
                            </TooltipTrigger>
                            <TooltipContent side="right">Migraciones</TooltipContent>
                        </Tooltip>

                        {/* Enlace adicional, si lo deseas (por ejemplo, configuración) */}
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Link
                                    href="/configuracion"
                                    className="group flex h-9 w-9 items-center justify-center gap-2 rounded-full bg-primary text-lg font-semibold text-primary-foreground md:h-8 md:w-8 md:text-base"
                                >
                                    <Settings className="h-5 w-5 transition-all group-hover:scale-110" />
                                    <span className="sr-only">Configuración</span>
                                </Link>
                            </TooltipTrigger>
                            <TooltipContent side="right">Configuración</TooltipContent>
                        </Tooltip>
                    </nav>
                </aside>

                <div className="flex flex-col sm:gap-4 sm:py-4 sm:pl-14">
                    <header className="sticky top-0 z-30 flex h-14 items-center gap-4 border-b bg-background px-4 sm:static sm:h-auto sm:border-0 sm:bg-transparent sm:px-6">
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button size="icon" variant="outline" className="sm:hidden">
                                    <PanelLeft className="h-5 w-5" />
                                    <span className="sr-only">Toggle Menu</span>
                                </Button>
                            </SheetTrigger>

                        </Sheet>
                        <Breadcrumb className="hidden md:flex">
                            <BreadcrumbList>
                                <BreadcrumbItem>
                                    <BreadcrumbLink asChild>
                                        <Link href="#">Reporte de Eventros Producción</Link>
                                    </BreadcrumbLink>
                                </BreadcrumbItem>


                            </BreadcrumbList>
                        </Breadcrumb>
                        <div className="relative ml-auto flex-1 md:grow-0">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder="Buscar..."
                                className="w-full rounded-lg bg-background pl-8 md:w-[200px] lg:w-[336px]"
                            />
                        </div>

                    </header>
                    <main className="grid flex-1 items-start gap-4 p-4 sm:px-6 sm:py-0 md:gap-8 lg:grid-cols-3 xl:grid-cols-3">
                        <div className="grid auto-rows-max items-start gap-4 md:gap-8 lg:col-span-2">
                            <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4">


                            </div>

                            {/* Tabs for 'Hoy', 'Mes', and 'Año' */}
                            <Tabs defaultValue="Hoy">
                                <div className="flex items-center">
                                    <TabsList>
                                        <TabsTrigger value="Hoy">Hoy</TabsTrigger>
                                        <TabsTrigger value="Mes">Mes</TabsTrigger>

                                    </TabsList>
                                    <div className="ml-auto flex items-center gap-2">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-7 gap-1 text-sm"
                                                >
                                                    <ListFilter className="h-3.5 w-3.5" />
                                                    <span className="sr-only sm:not-sr-only">Filter</span>
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>Filter by</DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuCheckboxItem checked>
                                                    Fulfilled
                                                </DropdownMenuCheckboxItem>
                                                <DropdownMenuCheckboxItem>
                                                    Declined
                                                </DropdownMenuCheckboxItem>
                                                <DropdownMenuCheckboxItem>
                                                    Refunded
                                                </DropdownMenuCheckboxItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            className="h-7 gap-1 text-sm"
                                            onClick={exportReport}
                                        >
                                            <File className="h-3.5 w-3.5" />
                                            <span className="sr-only sm:not-sr-only">Export</span>
                                        </Button>
                                    </div>
                                </div>

                                <TabsContent value="Hoy">
                                    <Card>
                                        <CardHeader className="px-7">
                                            <CardTitle>Eventos de Hoy</CardTitle>
                                            <CardDescription>
                                                Eventos generados por colaborador
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>Colaborador</TableHead>
                                                        <TableHead className="hidden sm:table-cell">
                                                            Sección
                                                        </TableHead>
                                                        <TableHead className="hidden sm:table-cell">
                                                            Tiempo
                                                        </TableHead>
                                                        <TableHead className="hidden md:table-cell">
                                                            Cant. Eventos
                                                        </TableHead>

                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {indicador2.map((evento, index) => (
                                                        <TableRow key={index} className="bg-accent">
                                                            <TableCell>
                                                                <div className="font-medium">{evento.Nombre_del_Colaborador}</div>
                                                                <div className="hidden text-sm text-muted-foreground md:inline">
                                                                    {evento.detalles}
                                                                </div>
                                                            </TableCell>
                                                            <TableCell className="hidden sm:table-cell">
                                                                {evento.Nombre_de_la_Seccion}
                                                            </TableCell>
                                                            <TableCell className="hidden sm:table-cell">
                                                                <Badge className="text-xs" variant="secondary">
                                                                    {evento.duracion_total_minutos}
                                                                </Badge>
                                                            </TableCell>
                                                            <TableCell className="hidden md:table-cell">
                                                                {evento.canteventos}
                                                            </TableCell>

                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </CardContent>
                                    </Card>
                                </TabsContent>
                                <TabsContent value="Mes">
                                    <Card>
                                        <CardHeader className="px-7">
                                            <CardTitle>Eventos del Mes</CardTitle>
                                            <CardDescription>
                                                Eventos generados por colaborador
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            {indicador3 && indicador3.length > 0 ? (
                                                indicador3.map((evento2, index) => (
                                                    <TableRow key={index} className="bg-accent">
                                                        <TableCell>
                                                            <div className="font-medium">{evento2.Nombre_del_Colaborador}</div>
                                                            <div className="hidden text-sm text-muted-foreground md:inline">
                                                                {evento2.detalles}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="hidden sm:table-cell">
                                                            {evento2.Nombre_de_la_Sección}
                                                        </TableCell>
                                                        <TableCell className="hidden sm:table-cell">
                                                            <Badge className="text-xs" variant="secondary">
                                                                {evento2.duracion_total_minutos}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="hidden md:table-cell">
                                                            {evento2.canteventos}
                                                        </TableCell>
                                                        <TableCell className="hidden md:table-cell">
                                                            {evento2.Fecha}
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            ) : (
                                                <tr>
                                                    <td colSpan="5" className="text-center">No hay datos disponibles</td>
                                                </tr>
                                            )}

                                        </CardContent>
                                    </Card>
                                </TabsContent>

                            </Tabs>
                        </div>
                    </main>
                    <main className="grid flex-1 items-start gap-2 p-4 sm:px-2 sm:py-0 md:gap-2 lg:grid-cols-3 xl:grid-cols-3">

                       {/*  <Card>
                            <CardHeader className="px-7">
                                <CardTitle>Eventos Colaborador</CardTitle>
                                <CardDescription>
                                    Eventos generados por colaborador
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Colaborador</TableHead>
                                            <TableHead className="hidden sm:table-cell">
                                                Sección
                                            </TableHead>
                                            <TableHead className="hidden sm:table-cell">
                                                Tiempo
                                            </TableHead>
                                            <TableHead className="hidden md:table-cell">
                                                Cant. Eventos
                                            </TableHead>
                                            <TableHead className="hidden md:table-cell">
                                                Fecha
                                            </TableHead>

                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>

                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card> */}

                    </main>
                </div>
            </div>
        </TooltipProvider>
    )
}

export default Dashboard;
