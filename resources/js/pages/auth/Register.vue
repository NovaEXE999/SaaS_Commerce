<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Crea tu cuenta de Negocio',
        description: 'Ingresa tus datos y los de tu negocio para comenzar',
    },
});
</script>

<template>
    <Head title="Registrar Negocio" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <!-- Datos del Negocio -->
            <div class="grid gap-2">
                <Label for="business_name">Nombre de tu Negocio</Label>
                <Input
                    id="business_name"
                    type="text"
                    required
                    v-focus
                    :tabindex="1"
                    name="business_name"
                    placeholder="Ej. Barbería Central"
                />
                <InputError :message="errors.business_name" />
            </div>

            <div class="grid gap-2">
                <Label for="business_type">Tipo de Negocio</Label>
                <Select name="business_type" :tabindex="2" required>
                    <SelectTrigger>
                        <SelectValue placeholder="Selecciona el tipo" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="tienda">Tienda / Minimarket</SelectItem>
                        <SelectItem value="papeleria">Papelería</SelectItem>
                        <SelectItem value="barberia">Barbería</SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.business_type" />
            </div>

            <!-- Datos del Administrador -->
            <div class="grid gap-2">
                <Label for="name">Tu Nombre (Admin)</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    :tabindex="3"
                    autocomplete="name"
                    name="name"
                    placeholder="Nombre completo"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Correo electrónico</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="4"
                    autocomplete="email"
                    name="email"
                    placeholder="correo@ejemplo.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Contraseña</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Contraseña segura"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirmar contraseña</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="6"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirmar contraseña"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="7"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" class="mr-2" />
                Registrar Negocio
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            ¿Ya tienes una cuenta?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="8"
                >Inicia Sesión</TextLink
            >
        </div>
    </Form>
</template>
