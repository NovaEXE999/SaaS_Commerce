# SAS COMMERCE - Multi-tenant SaaS Management System
> **Documento de Contexto, Estado del Proyecto y Checklist de Tareas (Roadmap)**
> *Este archivo sirve como referencia central para desarrolladores e inteligencias artificiales que retomen el desarrollo del proyecto en cualquier momento.*

---

## 1. Visión General del Proyecto
**SAS Commerce** es un sistema SaaS (Software as a Service) con arquitectura **Multi-tenant** diseñado para centralizar y gestionar las operaciones comerciales de negocios locales:
- **Tiendas / Minimarkets**
- **Papelerías**
- **Barberías y Salones de Belleza**

Cada negocio registrado actúa como un inquilino (*tenant*) aislado con sus propios usuarios, clientes, productos, ventas, citas y auditorías.

---

## 2. Stack Tecnológico y Entorno

| Componente | Tecnología | Detalle de Implementación |
| :--- | :--- | :--- |
| **Entorno Local** | Laragon (Windows) | PHP 8.3 CLI (`C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe`), MySQL, Nginx/Apache |
| **Backend** | Laravel 13 | Eloquent ORM, Migrations, Policies, Seeders, Jobs asíncronos |
| **Autenticación** | Laravel Fortify + Passkeys | Roles (`admin`, `empleado`), 2FA, Passkeys, Multi-tenant registration |
| **Frontend (SPA)** | Inertia.js v3 + Vue 3 | Composition API (`<script setup lang="ts">`), TypeScript |
| **Estilos & UI** | Tailwind CSS v4 + Shadcn-Vue / Reka UI | Lucide Icons, diseño responsivo, soporte Dark/Light mode |
| **Dashboard** | Bento Grid Layout | Tarjetas modulares adaptables al tipo de negocio |
| **Gráficos** | ApexCharts (`vue3-apexcharts`) | Reportes financieros dinámicos (Ingresos, Egresos, Flujo) |
| **Tiempo Real** | Laravel WebSockets / Reverb + Pusher-js | Eventos en vivo para turneros de barbería y alertas |
| **Automatización** | Webhooks hacia n8n | Notificaciones automáticas por WhatsApp / Correo |

---

## 3. Modelo de Datos y Relaciones (ERD)

### Entidades Principales:
1. **`businesses`**:
   - Campos: `id`, `name`, `type` (`enum['tienda', 'papeleria', 'barberia']`), `created_at`, `updated_at`.
   - Relaciones: `hasMany(User)`, `hasMany(Customer)`, `hasMany(Product)`, `hasMany(Sale)`, `hasMany(Appointment)`, `hasMany(Service)`.

2. **`users`**:
   - Campos: `id`, `business_id` (FK cascade), `name`, `email`, `password`, `role` (`enum['admin', 'empleado']`), `email_verified_at`, `remember_token`, `timestamps`, `softDeletes`.
   - Relaciones: `belongsTo(Business)`, `hasMany(Sale)`, `hasMany(Appointment)`.

3. **`customers`**:
   - Campos: `id`, `business_id` (FK cascade), `name`, `phone`, `points` (default 0), `timestamps`.
   - Relaciones: `belongsTo(Business)`, `hasMany(Appointment)`.

4. **`products`**:
   - Campos: `id`, `business_id` (FK cascade), `name`, `description`, `price` (decimal 10,2), `stock` (integer default 0), `timestamps`, `softDeletes`.
   - Relaciones: `belongsTo(Business)`.

5. **`sales`**:
   - Campos: `id`, `business_id` (FK cascade), `user_id` (FK cascade), `appointment_id` (FK nullable única, venta generada al completar cita), `total_amount` (decimal 10,2), `payment_method` (`enum['efectivo', 'transferencia', 'tarjeta']`), `payment_proof_path` (nullable string), `timestamps`.
   - Relaciones: `belongsTo(Business)`, `belongsTo(User)`, `belongsTo(Appointment)`, `hasMany(SaleItem)`.

6. **`sale_items`** *(Por implementar para desglose de factura/ticket)*:
   - Campos: `id`, `sale_id` (FK cascade), `product_id` (FK cascade), `quantity` (integer), `unit_price` (decimal 10,2), `subtotal` (decimal 10,2), `timestamps`.
   - Relaciones: `belongsTo(Sale)`, `belongsTo(Product)`.

7. **`appointments`**:
   - Campos: `id`, `business_id` (FK cascade), `customer_id` (FK cascade), `user_id` (FK cascade - barbero), `service_id` (FK nullable, `nullOnDelete`), `price` (decimal 10,2 snapshot del servicio al agendar), `start_time` (datetime), `status` (`enum['agendada', 'adelantada', 'reagendada', 'cancelada', 'completada']`), `justification` (text nullable), `client_approved` (boolean default false), `timestamps`.
   - Relaciones: `belongsTo(Business)`, `belongsTo(Customer)`, `belongsTo(User)`, `belongsTo(Service)`, `hasOne(Sale)`.
   - Al pasar a `completada` (con precio > 0) genera una `Sale` vinculada (`sales.appointment_id` única) con el método de pago elegido.

8. **`services`** *(Catálogo de servicios de barbería con precio)*:
   - Campos: `id`, `business_id` (FK cascade), `name`, `description` (nullable), `price` (decimal 10,2), `timestamps`.
   - Relaciones: `belongsTo(Business)`, `hasMany(Appointment)`.

9. **`audit_logs`** *(Módulo de Auditoría Global)*:
   - Registro de movimientos sensibles (devoluciones, cancelaciones con justificación, inhabilitación de productos/cuentas).

---

## 4. Reglas de Negocio Clave

1. **Aislamiento Multi-tenant:**
   - Todo query debe filtrar estrictamente por `business_id` del usuario autenticado (`$request->user()->business_id`).
   - El menú lateral (`AppSidebar.vue`) y el dashboard se adaptan según el `business.type` (`tienda`, `papeleria`, `barberia`) y el `user.role` (`admin`, `empleado`).
2. **Sistema de Auditoría Obligatorio:**
   - La cancelación o reagendamiento de una cita exige **justificación obligatoria**.
   - El adelanto de cita requiere confirmación de **aprobación explícita del cliente** (`client_approved = true`).
   - Las devoluciones o anulaciones de ventas deben pasar por aprobación del administrador con justificación.
3. **Fidelización y Puntos:**
   - Cada $5.000 COP en compras otorga **1 punto** acumulable al cliente registrado.
   - Puntos canjeables en compras o servicios posteriores.
4. **Comprobantes de Pago:**
   - Pagos por transferencia exigen la subida de un pantallazo/voucher (`payment_proof_path`) almacenado en storage público (`storage/payment_proofs`).
5. **Barbería y Turnero:**
   - Dos turneros: Uno local para pantalla física sincronizada (`/turnero`) y turnero digital para clientes.
   - Transmisión en tiempo real vía eventos broadcast (`TurnUpdated`).
   - Disparo asíncrono de Webhooks hacia n8n para recordatorios (1 día, 1 hora, 10 min antes).

---

## 5. Checklist de Progreso y Roadmap

### **Fase 1: Arquitectura Core y Autenticación**
- [x] Instalación y configuración de Laravel 11 en Laragon con MySQL.
- [x] Instalación de Inertia.js v3 + Vue 3 con TypeScript y Vite.
- [x] Configuración de Tailwind CSS v4 y componentes base Reka UI/Shadcn.
- [x] Migraciones base de base de datos (`businesses`, `users`, `customers`, `products`, `sales`, `appointments`).
- [x] Modelos de Eloquent con relaciones `BelongsTo`, `HasMany` y `SoftDeletes`.
- [x] Registro multi-tenant personalizado (`CreateNewUser.php` crea el Negocio y asigna al creador como `admin`).
- [x] Formulario de registro adaptado en Vue (`Register.vue` con selección de tipo de negocio).
- [x] Compartición de relación `business` del usuario en middleware `HandleInertiaRequests.php`.
- [x] Menú lateral de navegación dinámico (`AppSidebar.vue`) adaptado según `business.type` y rol `admin`.
- [x] Suite de pruebas automatizadas con Pest para Product, Sale y Appointment (100% passing).
- [x] Corrección de ParseError en docblock de `SendN8nWebhook.php` y activación de Webhooks a n8n.
- [x] Corrección de shadowing de tipos TypeScript en `resources/js/types/` (`vue-tsc --noEmit` sin errores).
- [x] Módulo de gestión e inhabilitación de empleados (`EmployeeController.php` + `Employees/Index.vue` con Soft Deletes, exclusivo de `admin`).
- [x] Suite de pruebas para Empleados (`EmployeeTest.php`) y Public Booking (`PublicBookingTest.php`).
- [x] Recuperación de contraseña en producción. **Verificación de correo desactivada temporalmente en fase de pruebas** (ver §7).

---

### **Fase 2: Módulo Tienda / Papelería / Inventario**
- [x] CRUD de productos (`ProductController.php` con filtros multi-tenant y paginación).
- [x] Inhabilitación de productos mediante Soft Deletes (`Product::destroy`).
- [x] Vista frontend de inventario (`resources/js/pages/Products/Index.vue`).
- [x] Punto de Venta (POS) para registro de ventas (`SaleController.php` con control de transacciones y lock de stock).
- [x] Interfaz de POS (`resources/js/pages/Sales/Create.vue`).
- [x] Carga de comprobantes de pago para transferencias (almacenamiento en `payment_proofs`).
- [x] Lógica de acumulación de puntos (1 punto por cada $5.000 COP).
- [x] Creación de tabla y modelo `sale_items` para almacenar el detalle de productos vendidos por factura.
- [x] Historial de ventas con visor de comprobantes de transferencia (`Sales/Index.vue` con modal de detalle de ticket, paginación y enlace a comprobantes).
- [x] Módulo de canje de puntos acumulados por descuentos o productos de regalo.
- [x] Alertas de stock mínimo configurables por producto (`min_stock` en productos con notificaciones en Dashboard).

---

### **Fase 3: Módulo Barbería**
- [x] Controlador de citas (`AppointmentController.php`) con agendamiento y validación de horarios.
- [x] Gestión de estados de cita: `agendada`, `adelantada`, `reagendada`, `cancelada`, `completada`.
- [x] Validación estricta de auditoría: justificación obligatoria para cancelación/reagendamiento.
- [x] Validación de adelanto: confirmación obligatoria de aprobación por el cliente.
- [x] Evento en tiempo real con WebSockets (`TurnUpdated.php`).
- [x] Pantalla pública del Turnero para TV / Sala de espera (`resources/js/pages/Appointments/TurnScreen.vue`).
- [x] Panel interno de citas para barberos/administrador (`resources/js/pages/Appointments/Index.vue`).
- [x] Job asíncrono `SendN8nWebhook.php` para disparar eventos a flujos de n8n.
- [x] Activación del disparo de webhooks en `AppointmentController` para `appointment_created` y `appointment_status_changed`.
- [x] Automatización de recordatorios programados (comando `php artisan appointments:notify-reminders` para 1 día, 1 hora y 10 minutos).
- [x] Landing pública inteligente (`PublicBookingController@index` + cookie `last_business_id` vía middleware `RememberLastBusiness`): primera visita → `WelcomePlatform.vue` (presentación NovaSaaS con stats reales); último negocio barbería → `Welcome.vue` (agendamiento online); tienda/papelería → `WelcomeBusiness.vue` (vitrina con catálogo destacado).
- [x] Emisión de eventos WebSocket en modo best-effort (`try/catch` + `Log::warning`): si Reverb no está corriendo, la cita igual se crea. Para tiempo real en local, levantar `php artisan reverb:start` en otra terminal.
- [x] Panel de gestión de clientes (`CustomerController.php` + `Customers/Index.vue`, CRUD con aislamiento multi-tenant para `admin` y `empleado`, con enlace en el menú lateral y acceso directo desde la Agenda).
- [x] Catálogo de servicios con precio (`services`: `ServiceController.php` + `Services/Index.vue`, CRUD multi-tenant para `admin` y `empleado`).
- [x] Servicio obligatorio al agendar (`appointments.service_id` + snapshot `appointments.price` que congela el precio aunque el catálogo cambie; reserva pública con servicio opcional).
- [x] Ingresos por citas completadas: al marcar `completada` se exige método de pago y se genera la `Sale` vinculada (`sales.appointment_id`, única) atribuida al barbero, alimentando Ventas Hoy y el gráfico de 7 días; el cliente acumula puntos (1 pt / $5.000). Las citas sin precio no generan venta.

---

### **Fase 4: Dashboard y Reportes (Bento Grid) & Auditoría Global**
- [x] Estructura base Bento Grid en `resources/js/pages/Dashboard.vue`.
- [x] Controlador de métricas de Dashboard (`DashboardController.php`) con consultas multi-tenant reales.
- [x] Instalación e integración de ApexCharts (`apexcharts` y `vue3-apexcharts`).
- [x] Gráficos financieros interactivos en el Dashboard (área de ventas últimos 7 días con gradiente).
- [x] Bandeja de Auditoría Global para Administrador (`AuditController.php` + `resources/js/pages/Audit/Index.vue`):
  - Supervisión de cancelaciones y reagendamientos con justificaciones obligatorias.
  - Validación de transferencias y acceso a vouchers de pago.
  - Revisión de productos y empleados inhabilitados (Soft Deletes).
- [x] Personalización del Dashboard según el tipo de negocio activo (`tienda`, `papeleria`, `barberia`).
- [x] Cobertura de pruebas automatizadas con Pest para Dashboard y Auditoría (`DashboardTest.php`, `AuditTest.php`).

---

## 6. Estructura de Rutas Actual (`routes/web.php`)

```text
GET   /                                  -> PublicBookingController@index (Landing inteligente: NovaSaaS / Barbería + Agendamiento / Tienda-Papelería + Vitrina)
POST  /public-booking                    -> PublicBookingController@store (Agendar cita sin auth)
GET   /dashboard                         -> DashboardController@index (Bento Grid + ApexCharts)
CRUD  /products                          -> ProductController (Gestión de inventario)
CRUD  /customers                         -> CustomerController (Gestión de clientes - admin y empleado)
GET   /sales                             -> SaleController@index (Historial de ventas + comprobantes)
GET   /sales/create                      -> SaleController@create (Punto de venta POS)
POST  /sales                             -> SaleController@store (Registro, stock, sale_items, puntos)
GET   /appointments                      -> AppointmentController@index (Agenda de barberos)
POST  /appointments                      -> AppointmentController@store (Agendar cita con servicio + n8n webhook)
PUT   /appointments/{appointment}/status -> AppointmentController@updateStatus (Auditoría justificada; completada exige pago y genera venta)
GET   /turnero                           -> AppointmentController@turnScreen (Pantalla TV)
CRUD  /services                          -> ServiceController (Catálogo de servicios con precio - admin y empleado)
POST  /point-redemptions                 -> PointRedemptionController@store (Canje manual de puntos)
CRUD  /employees                         -> EmployeeController (Gestión de personal - solo admin)
PUT   /employees/{employee}/restore      -> EmployeeController@restore (Reactivar cuenta)
GET   /audit                             -> AuditController@index (Bandeja de auditoría - solo admin)
```

---

## 7. Instrucciones para Continuar el Desarrollo
**Estado: sin tareas pendientes en fase de pruebas.** Todo el roadmap (Fases 1-4) está implementado y cubierto con Pest. Lo último completado:
- Módulo de Canje de Puntos (endpoint + UI en POS, 1 pt = $1.000).
- Alertas de Stock Mínimo (`min_stock` + Dashboard).
- Comando `appointments:notify-reminders` (1 día / 1 hora / 10 min) programado cada minuto.
- Ingresos por citas completadas (venta automática → métricas de 7 días).
- Landing pública inteligente (NovaSaaS / Barbería / Tienda-Papelería).

Única tarea restante (al salir a producción):

1. **Reactivar Verificación de Correo**: (1) en `app/Models/User.php` volver a implementar `MustVerifyEmail`, (2) descomentar `Features::emailVerification()` en `config/fortify.php`, (3) `php artisan wayfinder:generate` (restaura `resources/js/routes/verification`), (4) configurar SMTP en `.env` (`MAIL_MAILER=smtp`, etc.). El middleware `verified` ya está en las rutas y se reactivará solo. (Opcional: `VerifyEmail.vue` y `Profile.vue` usan la URL directa `/email/verification-notification`, que sigue válida; solo revertir a `send()` si se prefiere.)
