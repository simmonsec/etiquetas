import React, { useState, useEffect } from 'react';
import axios from 'axios';
import GuestLayout from '@/Layouts/GuestLayout';
import { Card } from '@/shadcn/ui/card';
import { Input } from '@/shadcn/ui/input';
import { Separator } from '@/shadcn/ui/separator';
import { Alert } from '@/shadcn/ui/alert';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/shadcn/ui/table';

import { Button } from "@/shadcn/ui/button"
import { Label } from "@/shadcn/ui/label"
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/shadcn/ui/sheet"

export default function ScanProduct() {
    const [code, setCode] = useState('');
    const [product, setProduct] = useState(null);
    const [error, setError] = useState('');
    const [scannedCodes, setScannedCodes] = useState([]);
    const [sheetOpen, setSheetOpen] = useState(false);

    const handleScan = async (e) => {
        setCode(e.target.value);

        if (e.target.value) {
            try {
                const response = await axios.get(`/api/product/${e.target.value}`);
                setProduct(response.data);
                setError('');
                setSheetOpen(true); // Abrir el Sheet al encontrar el producto
                fetchLatestScannedCodes(); // Actualizar la lista de consultas
            } catch (err) {
                setProduct(null);
                setError('Producto no encontrado');
            }
        } else {
            setProduct(null);
            setError('');
            setSheetOpen(false); // Cerrar el Sheet si no hay código ingresado
        }
    };

    const fetchLatestScannedCodes = async () => {
        try {
            const response = await axios.get('/api/scanned-codes/latest');
            setScannedCodes(response.data);
        } catch (err) {
            console.error('Error fetching latest scanned codes:', err);
        }
    };

    useEffect(() => {
        fetchLatestScannedCodes();
    }, []);

    return (

        <section className="bg-gray-900">
            <div className="py-2 px-2 mx-auto max-w-screen-xl text-center lg:py-10">
                <h1 className="mb-4 text-4xl font-extrabold tracking-tight leading-none md:text-5xl lg:text-6xl text-white">SISTEMAS DE ETIQUETAS</h1>
                <Input
                    value={code}
                    onChange={handleScan}
                    autoFocus
                    placeholder="Escanea el código aquí"
                    className="mt-4"
                />

                {product && (
                    <Card className="mt-4 p-4">
                        <h2 className="text-xl font-bold mb-4">Producto Encontrado</h2>
                        <Separator />
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <tbody className="text-lg">
                                    <tr>
                                        <td className="font-semibold w-[150px]">Descripción:</td>
                                        <td>{product.description}</td>
                                    </tr>
                                    <tr>
                                        <td className="font-semibold">Lote:</td>
                                        <td>{product.lote}</td>
                                    </tr>
                                    <tr>
                                        <td className="font-semibold">EAN13:</td>
                                        <td>{product.ean13}</td>
                                    </tr>
                                    <tr>
                                        <td className="font-semibold">EAN14:</td>
                                        <td>{product.ean14}</td>
                                    </tr>
                                    <tr>
                                        <td className="font-semibold">EAN128:</td>
                                        <td>{product.ean128}</td>
                                    </tr>
                                    <tr>
                                        <td className="font-semibold">Fecha:</td>
                                        <td>{product.fecha}</td>
                                    </tr>
                                    <tr>
                                        <td className="font-semibold">Código:</td>
                                        <td>{product.codigo}</td>
                                    </tr>
                                    <tr>
                                        <td className="font-semibold">Empresa:</td>
                                        <td>{product.empresa}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>


                )}
                {error && (
                    <Card className="mt-4" type="error">
                        <Separator />
                        <p>{error}</p>
                    </Card>
                )}
                <Card className="mt-8"> 
                    <Separator />
                    <Table>
                        <TableCaption>Últimas 20 consultas de códigos escaneados</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="text-left">Código de Barras</TableHead>
                                <TableHead className="text-left">Lote</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {scannedCodes.map((code, index) => (
                                <TableRow key={index}>
                                    <TableCell className="text-left">{code.barcode}</TableCell>
                                    <TableCell className="text-left">{code.lote}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </Card>
            </div>
            {product && (
                <div className="grid grid-cols-2 gap-2 mt-4">
                    <Sheet key="bottom" open={sheetOpen}>
                        <SheetTrigger asChild>
                            <Button variant="outline" className="text-lg">Ver Detalles</Button>
                        </SheetTrigger>
                        <SheetContent side="bottom">
                            <SheetHeader>
                                <SheetTitle className="text-2xl">{product.description}</SheetTitle>
                                <SheetDescription className="text-4xl">
                                    Lote: {product.lote}
                                </SheetDescription>
                            </SheetHeader>
                            <div className="grid gap-4 py-4">
                                <div className="grid grid-cols-4 items-center gap-4">
                                    <Label htmlFor="ean13" className="text-right text-4xl">
                                        EAN13:
                                    </Label>
                                    <span className="col-span-3 text-4xl">{product.ean13}</span>
                                </div>
                                <div className="grid grid-cols-4 items-center gap-4">
                                    <Label htmlFor="ean14" className="text-right text-4xl">
                                        EAN14:
                                    </Label>
                                    <span className="col-span-3 text-4xl">{product.ean14}</span>
                                </div>
                                <div className="grid grid-cols-4 items-center gap-4">
                                    <Label htmlFor="ean128" className="text-right text-4xl">
                                        EAN128:
                                    </Label>
                                    <span className="col-span-3 text-4xl">{product.ean128}</span>
                                </div>
                                <div className="grid grid-cols-4 items-center gap-4">
                                    <Label htmlFor="fecha" className="text-right text-4xl">
                                        Fecha:
                                    </Label>
                                    <span className="col-span-3 text-4xl">{product.fecha}</span>
                                </div>
                                <div className="grid grid-cols-4 items-center gap-4">
                                    <Label htmlFor="codigo" className="text-right text-4xl">
                                        Código:
                                    </Label>
                                    <span className="col-span-3 text-4xl">{product.codigo}</span>
                                </div>

                            </div>
                            <SheetFooter>
                                <SheetClose asChild>

                                </SheetClose>
                            </SheetFooter>
                        </SheetContent>
                    </Sheet>
                </div>
            )}
            {error && (
                <Card className="mt-4 text-lg" type="error">
                    <Separator />
                    <p>{error}</p>
                </Card>
            )}
        </section>
    );
}