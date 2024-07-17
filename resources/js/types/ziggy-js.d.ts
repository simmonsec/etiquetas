// resources/js/types/ziggy-js.d.ts
declare module 'ziggy-js' {
    interface ZiggyRoute {
        (name: string, params?: any, absolute?: boolean, customZiggy?: any): string;
        current(name?: string): boolean;
    }
    const route: ZiggyRoute;
    export default route;
}
