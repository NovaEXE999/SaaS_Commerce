<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    ShoppingCart,
    Package,
    CalendarDays,
    ShieldAlert,
    Settings,
    LifeBuoy,
    Tv,
    Users,
    Receipt,
    Scissors
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
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
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);
const businessType = computed(() => user.value?.business?.type);
const isAdmin = computed(() => user.value?.role === 'admin');

// Menú adaptado automáticamente a la regla Multi-tenant
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard Base',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (businessType.value === 'tienda' || businessType.value === 'papeleria') {
        items.push(
            {
                title: 'Ventas y Caja (POS)',
                href: '/sales/create',
                icon: ShoppingCart,
            },
            {
                title: 'Historial de Ventas',
                href: '/sales',
                icon: Receipt,
            },
            {
                title: 'Inventario',
                href: '/products',
                icon: Package,
            },
            {
                title: 'Clientes',
                href: '/customers',
                icon: Users,
            }
        );
    } else if (businessType.value === 'barberia') {
        items.push(
            {
                title: 'Agenda de Citas',
                href: '/appointments',
                icon: CalendarDays,
            },
            {
                title: 'Servicios y Precios',
                href: '/services',
                icon: Scissors,
            },
            {
                title: 'Clientes',
                href: '/customers',
                icon: Users,
            },
            {
                title: 'Pantalla Turnero',
                href: '/turnero',
                icon: Tv,
            }
        );
    } else {
        // En caso de que no tenga tipo asignado aún, mostrar opciones generales
        items.push(
            {
                title: 'Ventas y Caja',
                href: '/sales/create',
                icon: ShoppingCart,
            },
            {
                title: 'Inventario',
                href: '/products',
                icon: Package,
            },
            {
                title: 'Agenda de Citas',
                href: '/appointments',
                icon: CalendarDays,
            },
            {
                title: 'Clientes',
                href: '/customers',
                icon: Users,
            }
        );
    }

    if (isAdmin.value) {
        items.push(
            {
                title: 'Gestión de Personal',
                href: '/employees',
                icon: Users,
            },
            {
                title: 'Auditoría (Admin)',
                href: '/audit',
                icon: ShieldAlert,
            }
        );
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Ajustes de Negocio',
        href: '#',
        icon: Settings,
    },
    {
        title: 'Soporte',
        href: '#',
        icon: LifeBuoy,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
