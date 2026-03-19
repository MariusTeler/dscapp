import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as awbIndex } from '@/routes/awb';
import { index as shipmentsIndex } from '@/routes/shipments';
import { index as borderouriIndex } from '@/routes/slips';
import { index as destinatariIndex } from '@/routes/recipients';
import { index as comenziIndex } from '@/routes/orders';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { LucideBookPlus, LucideBookHeadphones, LayoutGrid, LucideClipboardSignature, LucideListFilter, LucideListCheck } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'AWB',
        href: awbIndex(),
        icon: LucideBookPlus,
    },
    {
        title: 'Liste expeditii',
        href: shipmentsIndex(),
        icon: LucideListCheck,
    },
    {
        title: 'Borderouri',
        href: borderouriIndex(),
        icon: LucideListFilter,
    },
    {
        title: 'Comenzi',
        href: comenziIndex(),
        icon: LucideBookHeadphones,
    },
    {
        title: 'Destinatari',
        href: destinatariIndex(),
        icon: LucideClipboardSignature,
    },
];

const footerNavItems: NavItem[] = [

];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()}>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
