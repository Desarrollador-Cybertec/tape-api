<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TAPE API — Documentación</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=jetbrains-mono:400,500" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0f172a;
            --bg-card: #1e293b;
            --bg-code: #0d1117;
            --border: #334155;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
            --text-heading: #f1f5f9;
            --accent: #38bdf8;
            --accent-dim: #0ea5e9;
            --green: #4ade80;
            --yellow: #fbbf24;
            --red: #f87171;
            --orange: #fb923c;
            --purple: #a78bfa;
            --pink: #f472b6;
            --cyan: #22d3ee;
        }

        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        /* ── Sidebar ── */
        .layout { display: flex; min-height: 100vh; }

        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: 280px;
            height: 100vh;
            background: #0f172a;
            border-right: 1px solid var(--border);
            overflow-y: auto;
            padding: 24px 0;
            z-index: 50;
        }

        .sidebar-logo {
            padding: 0 24px 20px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
        }

        .sidebar-logo h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: -0.5px;
        }

        .sidebar-logo p {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .sidebar nav { padding: 0 12px; }

        .sidebar-section {
            margin-bottom: 8px;
        }

        .sidebar-section-title {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            padding: 8px 12px 4px;
        }

        .sidebar a {
            display: block;
            padding: 6px 12px;
            font-size: 13px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.15s;
        }

        .sidebar a:hover {
            background: rgba(56, 189, 248, 0.08);
            color: var(--accent);
        }

        /* ── Main content ── */
        .main {
            margin-left: 280px;
            flex: 1;
            max-width: 960px;
            padding: 48px 48px 96px;
        }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, rgba(56,189,248,0.08), rgba(168,85,247,0.06));
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 48px;
            margin-bottom: 48px;
        }

        .hero h1 {
            font-size: 36px;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 12px;
        }

        .hero p { color: var(--text-muted); font-size: 16px; max-width: 600px; }

        .hero-badges {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 9999px;
            border: 1px solid var(--border);
            color: var(--text-muted);
            background: var(--bg-card);
        }

        .badge-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .badge-dot.green { background: var(--green); }
        .badge-dot.blue { background: var(--accent); }
        .badge-dot.purple { background: var(--purple); }
        .badge-dot.yellow { background: var(--yellow); }

        /* ── Sections ── */
        .section {
            margin-bottom: 48px;
            scroll-margin-top: 24px;
        }

        .section h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }

        .section h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-heading);
            margin: 24px 0 12px;
        }

        .section h4 {
            font-size: 15px;
            font-weight: 600;
            color: var(--accent);
            margin: 20px 0 8px;
        }

        .section p { margin-bottom: 12px; }

        /* ── Tables ── */
        .table-wrap {
            overflow-x: auto;
            margin-bottom: 16px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead { background: rgba(56, 189, 248, 0.06); }
        th {
            text-align: left;
            padding: 10px 14px;
            font-weight: 600;
            color: var(--text-heading);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 8px 14px;
            border-bottom: 1px solid rgba(51, 65, 85, 0.5);
            vertical-align: top;
        }

        tr:last-child td { border-bottom: none; }

        /* ── Code blocks ── */
        pre {
            background: var(--bg-code);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px 20px;
            overflow-x: auto;
            margin-bottom: 16px;
            font-size: 13px;
            line-height: 1.5;
        }

        code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
        }

        :not(pre) > code {
            background: rgba(56, 189, 248, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--accent);
            font-size: 12px;
        }

        /* ── Method badges ── */
        .method {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.5px;
            min-width: 52px;
            text-align: center;
        }

        .method-get { background: rgba(74, 222, 128, 0.15); color: var(--green); }
        .method-post { background: rgba(56, 189, 248, 0.15); color: var(--accent); }
        .method-put { background: rgba(251, 191, 36, 0.15); color: var(--yellow); }
        .method-patch { background: rgba(251, 146, 60, 0.15); color: var(--orange); }
        .method-delete { background: rgba(248, 113, 113, 0.15); color: var(--red); }

        .endpoint {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: var(--text);
        }

        .role-badge {
            display: inline-block;
            padding: 1px 6px;
            background: rgba(168,85,250,0.12);
            color: var(--purple);
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
            margin-right: 2px;
        }

        /* ── Flow diagram ── */
        .flow {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 16px;
            overflow-x: auto;
        }

        .flow-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .flow-node {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
            border: 1px solid var(--border);
            white-space: nowrap;
        }

        .flow-arrow { color: var(--text-muted); font-size: 16px; }

        .node-draft { background: rgba(148,163,184,0.1); color: var(--text-muted); }
        .node-pending { background: rgba(251,191,36,0.1); color: var(--yellow); border-color: rgba(251,191,36,0.3); }
        .node-progress { background: rgba(56,189,248,0.1); color: var(--accent); border-color: rgba(56,189,248,0.3); }
        .node-review { background: rgba(168,85,250,0.1); color: var(--purple); border-color: rgba(168,85,250,0.3); }
        .node-completed { background: rgba(74,222,128,0.1); color: var(--green); border-color: rgba(74,222,128,0.3); }
        .node-rejected { background: rgba(248,113,113,0.1); color: var(--red); border-color: rgba(248,113,113,0.3); }
        .node-overdue { background: rgba(251,146,60,0.1); color: var(--orange); border-color: rgba(251,146,60,0.3); }
        .node-cancelled { background: rgba(148,163,184,0.08); color: #64748b; border-color: rgba(148,163,184,0.2); }

        /* ── Cards ── */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 12px;
        }

        .card-title {
            font-weight: 600;
            color: var(--text-heading);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .card p { font-size: 13px; color: var(--text-muted); margin-bottom: 0; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

        /* ── Lists ── */
        ul { padding-left: 20px; margin-bottom: 12px; }
        li { margin-bottom: 4px; font-size: 14px; }

        /* ── Enum list ── */
        .enum-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }

        .enum-val {
            padding: 3px 10px;
            background: var(--bg-code);
            border: 1px solid var(--border);
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: var(--text);
        }

        /* ── Alert ── */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-info { background: rgba(56,189,248,0.08); border: 1px solid rgba(56,189,248,0.2); color: var(--accent); }
        .alert-warn { background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.2); color: var(--yellow); }
        .alert-icon { font-size: 16px; flex-shrink: 0; margin-top: 1px; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 24px 16px 64px; }
            .hero { padding: 28px 20px; }
            .hero h1 { font-size: 28px; }
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
    </style>
</head>
<body>
<div class="layout">

    {{-- ── Sidebar ── --}}
    <aside class="sidebar">
        <div class="sidebar-logo">
            <h1>TAPE API</h1>
            <p>v1.0 &middot; Laravel 12 &middot; Sanctum</p>
        </div>
        <nav>
            <div class="sidebar-section">
                <div class="sidebar-section-title">General</div>
                <a href="#overview">Descripción general</a>
                <a href="#stack">Stack técnico</a>
                <a href="#setup">Configuración</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Autenticación</div>
                <a href="#auth">Login / Logout / Me</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Endpoints</div>
                <a href="#users">Usuarios</a>
                <a href="#areas">Áreas</a>
                <a href="#meetings">Reuniones</a>
                <a href="#tasks">Tareas</a>
                <a href="#task-updates">Avances de tareas</a>
                <a href="#dashboard">Dashboard</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Negocio</div>
                <a href="#models">Modelos y relaciones</a>
                <a href="#enums">Enums</a>
                <a href="#flow">Flujo de estados</a>
                <a href="#rules">Reglas de negocio</a>
                <a href="#notifications">Notificaciones</a>
                <a href="#commands">Comandos programados</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Referencia</div>
                <a href="#responses">Formato de respuestas</a>
                <a href="#security">Seguridad</a>
                <a href="#migrations">Migraciones</a>
                <a href="#tests">Tests</a>
            </div>
        </nav>
    </aside>

    {{-- ── Main content ── --}}
    <main class="main">

        {{-- Hero --}}
        <div class="hero" id="overview">
            <h1>TAPE API</h1>
            <p>
                API REST para el sistema de gestión de compromisos y tareas, construida con Laravel 12 y Supabase.
                Reemplaza el proceso manual basado en Excel, proporcionando registro individual de compromisos,
                seguimiento de avances, notificaciones automáticas consolidadas y dashboards analíticos.
            </p>
            <div class="hero-badges">
                <span class="badge"><span class="badge-dot green"></span> 80 tests passing</span>
                <span class="badge"><span class="badge-dot blue"></span> Laravel 12</span>
                <span class="badge"><span class="badge-dot purple"></span> Sanctum Auth</span>
                <span class="badge"><span class="badge-dot yellow"></span> PostgreSQL / Supabase</span>
            </div>
        </div>

        {{-- ═══════════════ STACK ═══════════════ --}}
        <div class="section" id="stack">
            <h2>Stack Técnico</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Componente</th><th>Tecnología</th></tr></thead>
                    <tbody>
                        <tr><td>Framework</td><td>Laravel 12</td></tr>
                        <tr><td>PHP</td><td>8.4+</td></tr>
                        <tr><td>Base de datos</td><td>PostgreSQL (Supabase)</td></tr>
                        <tr><td>Autenticación</td><td>Laravel Sanctum (tokens)</td></tr>
                        <tr><td>Tests</td><td>PHPUnit 11 (SQLite :memory:)</td></tr>
                        <tr><td>Rate Limiting</td><td>60 requests/minuto</td></tr>
                        <tr><td>Scheduler</td><td>Laravel Scheduler (cron)</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══════════════ SETUP ═══════════════ --}}
        <div class="section" id="setup">
            <h2>Configuración Inicial</h2>

            <h3>1. Variables de entorno</h3>
            <p>Copiar <code>.env.example</code> a <code>.env</code> y completar los datos de Supabase:</p>
            <pre><code>DB_CONNECTION=pgsql
DB_HOST=db.XXXXX.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=tu_password</code></pre>

            <h3>2. Instalación</h3>
            <pre><code>composer install
php artisan key:generate
php artisan migrate --seed</code></pre>

            <div class="card">
                <div class="card-title">Datos del seeder</div>
                <p>
                    <strong>3 roles:</strong> superadmin, area_manager, worker<br>
                    <strong>Superadmin:</strong> admin@tape.test / Password1<br>
                    <strong>Manager:</strong> manager@tape.test / Password1<br>
                    <strong>Extras:</strong> 2 trabajadores, 1 área con membresías, 1 reunión de ejemplo
                </p>
            </div>

            <h3>3. Ejecutar tests</h3>
            <pre><code>php artisan test
# o
php vendor/bin/phpunit --testdox</code></pre>

            <h3>4. Scheduler (producción)</h3>
            <pre><code>* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1</code></pre>
        </div>

        {{-- ═══════════════ AUTH ═══════════════ --}}
        <div class="section" id="auth">
            <h2>Autenticación</h2>

            <div class="alert alert-info">
                <span class="alert-icon">&#9432;</span>
                <span>Todos los endpoints (excepto <code>POST /login</code>) requieren el header <code>Authorization: Bearer {'{token}'}</code></span>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Método</th><th>Endpoint</th><th>Descripción</th><th>Auth</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/login</td>
                            <td>Login con email y password</td>
                            <td>No</td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/logout</td>
                            <td>Cerrar sesión (revoca token)</td>
                            <td>Sí</td>
                        </tr>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/me</td>
                            <td>Perfil del usuario autenticado</td>
                            <td>Sí</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4>Request — Login</h4>
            <pre><code>POST /api/login
Content-Type: application/json

{
    "email": "admin@tape.test",
    "password": "Password1"
}</code></pre>

            <h4>Response — Login exitoso</h4>
            <pre><code>{
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "Admin",
        "email": "admin@tape.test",
        "role": {
            "id": 1,
            "name": "Super Administrador",
            "slug": "superadmin"
        },
        "active": true
    }
}</code></pre>
        </div>

        {{-- ═══════════════ USERS ═══════════════ --}}
        <div class="section" id="users">
            <h2>Usuarios</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Método</th><th>Endpoint</th><th>Descripción</th><th>Roles</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/users</td>
                            <td>Listar usuarios</td>
                            <td><span class="role-badge">superadmin</span> <span class="role-badge">area_manager</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/users</td>
                            <td>Crear usuario</td>
                            <td><span class="role-badge">superadmin</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/users/{'{id}'}</td>
                            <td>Ver usuario</td>
                            <td><span class="role-badge">superadmin</span> <span class="role-badge">area_manager</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-put">PUT</span></td>
                            <td class="endpoint">/api/users/{'{id}'}</td>
                            <td>Actualizar usuario</td>
                            <td><span class="role-badge">superadmin</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-patch">PATCH</span></td>
                            <td class="endpoint">/api/users/{'{id}'}/role</td>
                            <td>Cambiar rol</td>
                            <td><span class="role-badge">superadmin</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-patch">PATCH</span></td>
                            <td class="endpoint">/api/users/{'{id}'}/toggle-active</td>
                            <td>Activar / desactivar</td>
                            <td><span class="role-badge">superadmin</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4>Body — Crear usuario</h4>
            <pre><code>{
    "name": "Juan Pérez",
    "email": "juan@empresa.com",
    "password": "Segur@123",
    "password_confirmation": "Segur@123",
    "role_id": 3
}</code></pre>
            <p>Password requiere: mínimo 8 caracteres, mayúsculas, minúsculas, números y símbolos.</p>
        </div>

        {{-- ═══════════════ AREAS ═══════════════ --}}
        <div class="section" id="areas">
            <h2>Áreas</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Método</th><th>Endpoint</th><th>Descripción</th><th>Roles</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/areas</td>
                            <td>Listar áreas</td>
                            <td><span class="role-badge">superadmin</span> <span class="role-badge">area_manager</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/areas</td>
                            <td>Crear área</td>
                            <td><span class="role-badge">superadmin</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/areas/{'{id}'}</td>
                            <td>Ver área</td>
                            <td><span class="role-badge">superadmin</span> <span class="role-badge">area_manager</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-put">PUT</span></td>
                            <td class="endpoint">/api/areas/{'{id}'}</td>
                            <td>Actualizar área</td>
                            <td><span class="role-badge">superadmin</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-patch">PATCH</span></td>
                            <td class="endpoint">/api/areas/{'{id}'}/manager</td>
                            <td>Asignar encargado</td>
                            <td><span class="role-badge">superadmin</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/areas/claim-worker</td>
                            <td>Reclamar trabajador al área</td>
                            <td><span class="role-badge">area_manager</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4>Body — Crear área</h4>
            <pre><code>{
    "name": "Gerencia Comercial",
    "description": "Área de ventas y relaciones comerciales"
}</code></pre>

            <h4>Body — Reclamar trabajador</h4>
            <pre><code>{
    "user_id": 5
}</code></pre>
            <p>El trabajador debe tener rol <code>worker</code> y no pertenecer a otra área activa.</p>
        </div>

        {{-- ═══════════════ MEETINGS ═══════════════ --}}
        <div class="section" id="meetings">
            <h2>Reuniones</h2>
            <p>Las reuniones son el origen de los compromisos. Las tareas pueden vincularse a una reunión mediante <code>meeting_id</code>.</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Método</th><th>Endpoint</th><th>Descripción</th><th>Roles</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/meetings</td>
                            <td>Listar reuniones (filtrable por área y clasificación)</td>
                            <td><span class="role-badge">superadmin</span> <span class="role-badge">area_manager</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/meetings</td>
                            <td>Crear reunión</td>
                            <td><span class="role-badge">superadmin</span> <span class="role-badge">area_manager</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/meetings/{'{id}'}</td>
                            <td>Ver reunión con tareas asociadas</td>
                            <td>Según visibilidad</td>
                        </tr>
                        <tr>
                            <td><span class="method method-put">PUT</span></td>
                            <td class="endpoint">/api/meetings/{'{id}'}</td>
                            <td>Actualizar reunión</td>
                            <td>Creador o <span class="role-badge">superadmin</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-delete">DELETE</span></td>
                            <td class="endpoint">/api/meetings/{'{id}'}</td>
                            <td>Eliminar reunión</td>
                            <td><span class="role-badge">superadmin</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4>Query params — Filtrar reuniones</h4>
            <pre><code>GET /api/meetings?area_id=1&classification=operational</code></pre>

            <h4>Body — Crear reunión</h4>
            <pre><code>{
    "title": "Revisión mensual de avances",
    "meeting_date": "2026-03-20",
    "area_id": 1,
    "classification": "follow_up",
    "notes": "Revisar compromisos pendientes del mes anterior"
}</code></pre>

            <h4>Clasificaciones disponibles</h4>
            <div class="enum-list">
                <span class="enum-val">strategic</span>
                <span class="enum-val">operational</span>
                <span class="enum-val">follow_up</span>
                <span class="enum-val">review</span>
                <span class="enum-val">other</span>
            </div>
        </div>

        {{-- ═══════════════ TASKS ═══════════════ --}}
        <div class="section" id="tasks">
            <h2>Tareas</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Método</th><th>Endpoint</th><th>Descripción</th><th>Roles</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/tasks</td>
                            <td>Listar tareas (filtrado por rol)</td>
                            <td>Todos</td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/tasks</td>
                            <td>Crear tarea</td>
                            <td><span class="role-badge">superadmin</span> <span class="role-badge">area_manager</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/tasks/{'{id}'}</td>
                            <td>Ver tarea con detalles completos</td>
                            <td>Según visibilidad</td>
                        </tr>
                        <tr>
                            <td><span class="method method-put">PUT</span></td>
                            <td class="endpoint">/api/tasks/{'{id}'}</td>
                            <td>Actualizar tarea</td>
                            <td><span class="role-badge">superadmin</span> <span class="role-badge">area_manager</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/tasks/{'{id}'}/delegate</td>
                            <td>Delegar tarea a otro trabajador</td>
                            <td><span class="role-badge">area_manager</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/tasks/{'{id}'}/start</td>
                            <td>Iniciar tarea</td>
                            <td>Worker asignado</td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/tasks/{'{id}'}/submit-review</td>
                            <td>Enviar a revisión</td>
                            <td>Worker asignado</td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/tasks/{'{id}'}/approve</td>
                            <td>Aprobar tarea</td>
                            <td><span class="role-badge">area_manager</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/tasks/{'{id}'}/reject</td>
                            <td>Rechazar tarea</td>
                            <td><span class="role-badge">area_manager</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/tasks/{'{id}'}/cancel</td>
                            <td>Cancelar tarea</td>
                            <td><span class="role-badge">superadmin</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/tasks/{'{id}'}/comment</td>
                            <td>Agregar comentario</td>
                            <td>Según visibilidad</td>
                        </tr>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/tasks/{'{id}'}/attachments</td>
                            <td>Subir adjunto</td>
                            <td>Según visibilidad</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4>Body — Crear tarea</h4>
            <pre><code>{
    "title": "Preparar informe trimestral",
    "description": "Consolidar datos de ventas Q1 2026",
    "priority": "high",
    "due_date": "2026-04-01",
    "assigned_to_user_id": 4,
    "meeting_id": 1,
    "requires_attachment": true,
    "requires_completion_comment": true,
    "requires_manager_approval": true,
    "requires_progress_report": true,
    "notify_on_due": true,
    "notify_on_overdue": true,
    "notify_on_completion": false
}</code></pre>

            <h4>Casos de asignación</h4>
            <div class="grid-2">
                <div class="card">
                    <div class="card-title">Asignar a usuario</div>
                    <p>Enviar <code>assigned_to_user_id</code>. Estado inicial: <code>pending</code>.</p>
                </div>
                <div class="card">
                    <div class="card-title">Asignar a área</div>
                    <p>Enviar <code>assigned_to_area_id</code>. Estado inicial: <code>pending_assignment</code>. El encargado luego delega.</p>
                </div>
            </div>

            <h4>Body — Delegar</h4>
            <pre><code>{
    "to_user_id": 5,
    "note": "Necesito que te encargues de esta tarea"
}</code></pre>

            <h4>Body — Enviar a revisión</h4>
            <pre><code>{
    "completion_comment": "Trabajo completado, adjunté el informe final"
}</code></pre>
            <p>Si <code>requires_attachment = true</code>, la tarea debe tener al menos un adjunto antes de enviar a revisión.</p>

            <h4>Body — Rechazar</h4>
            <pre><code>{
    "rejection_note": "Falta el desglose por región, favor completar"
}</code></pre>

            <h4>Body — Subir adjunto</h4>
            <pre><code>POST /api/tasks/{'{id}'}/attachments
Content-Type: multipart/form-data

file: [archivo]
attachment_type: evidence | support | final_delivery</code></pre>
        </div>

        {{-- ═══════════════ TASK UPDATES ═══════════════ --}}
        <div class="section" id="task-updates">
            <h2>Avances de Tareas</h2>
            <p>Permite al responsable o manager reportar avances con porcentaje de progreso.</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Método</th><th>Endpoint</th><th>Descripción</th><th>Roles</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method method-post">POST</span></td>
                            <td class="endpoint">/api/tasks/{'{id}'}/updates</td>
                            <td>Reportar avance/progreso</td>
                            <td>Responsable, <span class="role-badge">area_manager</span>, <span class="role-badge">superadmin</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4>Body — Reportar avance</h4>
            <pre><code>{
    "comment": "Se completó la fase de recolección de datos",
    "update_type": "progress",
    "progress_percent": 65
}</code></pre>

            <h4>Tipos de actualización</h4>
            <div class="enum-list">
                <span class="enum-val">progress</span>
                <span class="enum-val">evidence</span>
                <span class="enum-val">note</span>
            </div>

            <div class="alert alert-info">
                <span class="alert-icon">&#9432;</span>
                <span>El <code>progress_percent</code> se sincroniza automáticamente con el campo <code>progress_percent</code> de la tarea.</span>
            </div>
        </div>

        {{-- ═══════════════ DASHBOARD ═══════════════ --}}
        <div class="section" id="dashboard">
            <h2>Dashboard</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Método</th><th>Endpoint</th><th>Descripción</th><th>Roles</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/dashboard/general</td>
                            <td>Dashboard gerencial general</td>
                            <td><span class="role-badge">superadmin</span></td>
                        </tr>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/dashboard/area/{'{id}'}</td>
                            <td>Dashboard por área</td>
                            <td><span class="role-badge">superadmin</span> <span class="role-badge">area_manager</span> del área</td>
                        </tr>
                        <tr>
                            <td><span class="method method-get">GET</span></td>
                            <td class="endpoint">/api/dashboard/me</td>
                            <td>Dashboard personal</td>
                            <td>Todos</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Dashboard General</h3>
            <div class="grid-3">
                <div class="card">
                    <div class="card-title">Conteos</div>
                    <p>Total activas, completadas, vencidas, próximas a vencer, completadas este mes</p>
                </div>
                <div class="card">
                    <div class="card-title">Métricas</div>
                    <p>Tasa de cierre (%), promedio de días para cerrar</p>
                </div>
                <div class="card">
                    <div class="card-title">Rankings</div>
                    <p>Tareas por estado, por área, top 10 responsables por carga</p>
                </div>
            </div>

            <h3>Dashboard por Área</h3>
            <p>Tareas por estado del área, vencidas, distribución por responsable, tasa de cierre, tareas sin reportes de avance.</p>

            <h3>Dashboard Personal</h3>
            <p>Tareas activas, vencidas, próximas a vencer, completadas, distribución por estado, lista de tareas próximas ordenadas por fecha.</p>
        </div>

        {{-- ═══════════════ MODELS ═══════════════ --}}
        <div class="section" id="models">
            <h2>Modelos y Relaciones</h2>

            <h3>User</h3>
            <ul>
                <li><code>belongsTo</code> Role</li>
                <li><code>belongsToMany</code> Area (via <code>area_members</code>)</li>
                <li><code>hasMany</code> createdTasks, assignedTasks, responsibleTasks</li>
                <li>Helpers: <code>isSuperAdmin()</code>, <code>isAreaManager()</code>, <code>isWorker()</code>, <code>belongsToArea($id)</code>, <code>isManagerOfArea($id)</code></li>
            </ul>

            <h3>Area</h3>
            <ul>
                <li><code>belongsTo</code> manager (User)</li>
                <li><code>belongsToMany</code> users (via <code>area_members</code>)</li>
                <li><code>hasMany</code> Tasks</li>
                <li>Campos: <code>name</code>, <code>description</code>, <code>manager_user_id</code>, <code>active</code></li>
            </ul>

            <h3>Task (SoftDeletes)</h3>
            <ul>
                <li><code>belongsTo</code> creator, assignedUser, assignedArea, delegatedBy, currentResponsible, area, closedBy, cancelledBy, meeting</li>
                <li><code>hasMany</code> comments, attachments, statusHistory, delegations, updates</li>
                <li>Config: <code>requires_attachment</code>, <code>requires_completion_comment</code>, <code>requires_manager_approval</code>, <code>requires_progress_report</code></li>
                <li>Notificación: <code>notify_on_due</code>, <code>notify_on_overdue</code>, <code>notify_on_completion</code></li>
                <li>Seguimiento: <code>progress_percent</code>, <code>meeting_id</code></li>
            </ul>

            <h3>Meeting</h3>
            <ul>
                <li><code>belongsTo</code> Area, creator (User)</li>
                <li><code>hasMany</code> Tasks</li>
                <li>Campos: <code>title</code>, <code>meeting_date</code>, <code>area_id</code>, <code>classification</code>, <code>notes</code>, <code>created_by</code></li>
            </ul>

            <h3>TaskUpdate</h3>
            <ul>
                <li><code>belongsTo</code> Task, User</li>
                <li>Campos: <code>task_id</code>, <code>user_id</code>, <code>update_type</code>, <code>comment</code>, <code>progress_percent</code></li>
            </ul>

            <h3>Otros modelos</h3>
            <ul>
                <li><strong>TaskDelegation</strong> — Historial de delegaciones (<code>task_id</code>, <code>from_user</code>, <code>to_user</code>)</li>
                <li><strong>TaskComment</strong> — Comentarios (<code>task_id</code>, <code>user_id</code>, <code>comment</code>, <code>type</code>)</li>
                <li><strong>TaskAttachment</strong> — Archivos adjuntos (<code>file_name</code>, <code>file_path</code>, <code>mime_type</code>, <code>file_size</code>)</li>
                <li><strong>TaskStatusHistory</strong> — Cambios de estado (<code>from_status</code>, <code>to_status</code>, <code>note</code>)</li>
                <li><strong>ActivityLog</strong> — Auditoría (<code>module</code>, <code>action</code>, <code>subject_type</code>, <code>description</code>)</li>
            </ul>
        </div>

        {{-- ═══════════════ ENUMS ═══════════════ --}}
        <div class="section" id="enums">
            <h2>Enums</h2>

            <h4>TaskStatusEnum</h4>
            <div class="enum-list">
                <span class="enum-val">draft</span>
                <span class="enum-val">pending_assignment</span>
                <span class="enum-val">pending</span>
                <span class="enum-val">in_progress</span>
                <span class="enum-val">in_review</span>
                <span class="enum-val">completed</span>
                <span class="enum-val">rejected</span>
                <span class="enum-val">overdue</span>
                <span class="enum-val">cancelled</span>
            </div>

            <h4>TaskPriorityEnum</h4>
            <div class="enum-list">
                <span class="enum-val">low</span>
                <span class="enum-val">medium</span>
                <span class="enum-val">high</span>
                <span class="enum-val">urgent</span>
            </div>

            <h4>RoleEnum</h4>
            <div class="enum-list">
                <span class="enum-val">superadmin</span>
                <span class="enum-val">area_manager</span>
                <span class="enum-val">worker</span>
            </div>

            <h4>CommentTypeEnum</h4>
            <div class="enum-list">
                <span class="enum-val">comment</span>
                <span class="enum-val">progress</span>
                <span class="enum-val">completion_note</span>
                <span class="enum-val">rejection_note</span>
                <span class="enum-val">system</span>
            </div>

            <h4>AttachmentTypeEnum</h4>
            <div class="enum-list">
                <span class="enum-val">evidence</span>
                <span class="enum-val">support</span>
                <span class="enum-val">final_delivery</span>
            </div>

            <h4>UpdateTypeEnum</h4>
            <div class="enum-list">
                <span class="enum-val">progress</span>
                <span class="enum-val">evidence</span>
                <span class="enum-val">note</span>
            </div>

            <h4>MeetingClassificationEnum</h4>
            <div class="enum-list">
                <span class="enum-val">strategic</span>
                <span class="enum-val">operational</span>
                <span class="enum-val">follow_up</span>
                <span class="enum-val">review</span>
                <span class="enum-val">other</span>
            </div>
        </div>

        {{-- ═══════════════ FLOW ═══════════════ --}}
        <div class="section" id="flow">
            <h2>Flujo de Estados</h2>

            <h4>Flujo principal</h4>
            <div class="flow">
                <div class="flow-row">
                    <span class="flow-node node-draft">draft</span>
                    <span class="flow-arrow">&rarr;</span>
                    <span class="flow-node node-pending">pending_assignment</span>
                    <span class="flow-arrow">&rarr;</span>
                    <span class="flow-node node-pending">pending</span>
                    <span class="flow-arrow">&rarr;</span>
                    <span class="flow-node node-progress">in_progress</span>
                    <span class="flow-arrow">&rarr;</span>
                    <span class="flow-node node-review">in_review</span>
                    <span class="flow-arrow">&rarr;</span>
                    <span class="flow-node node-completed">completed</span>
                </div>
            </div>

            <h4>Transiciones alternativas</h4>
            <div class="flow">
                <div class="flow-row">
                    <span class="flow-node node-review">in_review</span>
                    <span class="flow-arrow">&rarr;</span>
                    <span class="flow-node node-rejected">rejected</span>
                    <span class="flow-arrow">&rarr;</span>
                    <span class="flow-node node-progress">in_progress</span>
                    <span class="flow-arrow">(retry)</span>
                </div>
                <div class="flow-row" style="margin-top:8px">
                    <span class="flow-node node-progress">in_progress</span>
                    <span class="flow-arrow">&rarr;</span>
                    <span class="flow-node node-overdue">overdue</span>
                    <span class="flow-arrow">&rarr;</span>
                    <span class="flow-node node-progress">in_progress</span>
                    <span class="flow-arrow">(reiniciar)</span>
                </div>
                <div class="flow-row" style="margin-top:8px">
                    <span class="flow-node node-progress">cualquier estado activo</span>
                    <span class="flow-arrow">&rarr;</span>
                    <span class="flow-node node-cancelled">cancelled</span>
                </div>
            </div>

            <h4>Tabla de transiciones</h4>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Desde</th><th>Hacia</th></tr></thead>
                    <tbody>
                        <tr><td><code>draft</code></td><td>pending_assignment, pending, cancelled</td></tr>
                        <tr><td><code>pending_assignment</code></td><td>pending, cancelled</td></tr>
                        <tr><td><code>pending</code></td><td>in_progress, cancelled</td></tr>
                        <tr><td><code>in_progress</code></td><td>in_review, completed, cancelled, overdue</td></tr>
                        <tr><td><code>in_review</code></td><td>completed, rejected, cancelled</td></tr>
                        <tr><td><code>rejected</code></td><td>in_progress, cancelled</td></tr>
                        <tr><td><code>overdue</code></td><td>in_progress, cancelled</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══════════════ RULES ═══════════════ --}}
        <div class="section" id="rules">
            <h2>Reglas de Negocio</h2>

            <h3>Creación de tareas</h3>
            <div class="grid-2">
                <div class="card">
                    <div class="card-title">Asignar a usuario</div>
                    <p>
                        <code>assigned_to_user_id</code> = usuario<br>
                        <code>current_responsible_user_id</code> = usuario<br>
                        Estado: <code>pending</code>
                    </p>
                </div>
                <div class="card">
                    <div class="card-title">Asignar a área</div>
                    <p>
                        <code>assigned_to_area_id</code> = área<br>
                        <code>current_responsible_user_id</code> = null<br>
                        Estado: <code>pending_assignment</code>
                    </p>
                </div>
            </div>

            <h3>Delegación</h3>
            <ul>
                <li>Solo el encargado del área puede delegar tareas de su área</li>
                <li>El trabajador destino debe pertenecer al área</li>
                <li>La tarea no puede estar completada ni cancelada</li>
                <li>Se registra en <code>task_delegations</code> y <code>activity_logs</code></li>
            </ul>

            <h3>Validaciones de cierre</h3>
            <ul>
                <li>Si <code>requires_attachment = true</code> &rarr; debe tener al menos un adjunto</li>
                <li>Si <code>requires_completion_comment = true</code> &rarr; debe enviar <code>completion_comment</code></li>
            </ul>

            <h3>Aprobación / Rechazo</h3>
            <ul>
                <li>Si <code>requires_manager_approval = true</code> &rarr; pasa a <code>in_review</code> antes de <code>completed</code></li>
                <li>Si no requiere aprobación &rarr; pasa directamente a <code>completed</code></li>
                <li>Al rechazar &rarr; requiere <code>rejection_note</code>, estado vuelve a <code>in_progress</code></li>
            </ul>

            <h3>Reclamar trabajador</h3>
            <ul>
                <li>Solo <code>area_manager</code> puede reclamar</li>
                <li>El usuario debe tener rol <code>worker</code></li>
                <li>El trabajador no debe tener ya un área activa</li>
            </ul>

            <h3>Reuniones como origen</h3>
            <ul>
                <li>Las tareas pueden tener <code>meeting_id</code> vinculándolas a la reunión origen</li>
                <li>Al ver una reunión se listan todas sus tareas asociadas</li>
            </ul>

            <h3>Seguimiento y avances</h3>
            <ul>
                <li>El responsable puede reportar avances con comentario, porcentaje y tipo</li>
                <li>El <code>progress_percent</code> se sincroniza automáticamente con la tarea</li>
                <li>Si <code>requires_progress_report = true</code>, la tarea exige reportes de avance</li>
            </ul>
        </div>

        {{-- ═══════════════ NOTIFICATIONS ═══════════════ --}}
        <div class="section" id="notifications">
            <h2>Notificaciones Automáticas</h2>
            <div class="grid-3">
                <div class="card">
                    <div class="card-title">notify_on_due</div>
                    <p>Alerta cuando la fecha de entrega está a 3 días o menos</p>
                </div>
                <div class="card">
                    <div class="card-title">notify_on_overdue</div>
                    <p>Alerta cuando la tarea ya pasó su fecha de vencimiento</p>
                </div>
                <div class="card">
                    <div class="card-title">notify_on_completion</div>
                    <p>Notificación automática al completar la tarea</p>
                </div>
            </div>
            <p>Los comandos programados generan registros en la tabla <code>task_notifications</code>, que luego pueden ser consumidos por un servicio de email o push notifications.</p>
        </div>

        {{-- ═══════════════ COMMANDS ═══════════════ --}}
        <div class="section" id="commands">
            <h2>Comandos Programados</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Comando</th><th>Descripción</th><th>Horario</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><code>tasks:detect-overdue</code></td>
                            <td>Marca como vencidas las tareas pasadas de fecha. Cambia el estado a <code>overdue</code> y registra en <code>task_status_history</code> y <code>activity_logs</code>.</td>
                            <td>06:00 diario</td>
                        </tr>
                        <tr>
                            <td><code>tasks:send-daily-summary</code></td>
                            <td>Genera resúmenes consolidados por responsable. Agrupa tareas activas, cuenta vencidas y próximas a vencer, y crea un <code>TaskNotification</code> por usuario.</td>
                            <td>07:00 diario</td>
                        </tr>
                        <tr>
                            <td><code>tasks:send-due-reminders</code></td>
                            <td>Envía recordatorios individuales para tareas con <code>notify_on_due</code> próximas a vencer (3 días) y alertas para tareas con <code>notify_on_overdue</code> ya vencidas.</td>
                            <td>08:00 diario</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══════════════ RESPONSES ═══════════════ --}}
        <div class="section" id="responses">
            <h2>Formato de Respuestas</h2>

            <h4>Éxito con datos (200)</h4>
            <pre><code>{
    "data": {
        "id": 1,
        "title": "Tarea ejemplo",
        "status": "pending",
        "priority": "high",
        "progress_percent": 0,
        "is_overdue": false,
        "created_at": "2026-03-16T10:00:00.000000Z"
    }
}</code></pre>

            <h4>Éxito con mensaje (200)</h4>
            <pre><code>{
    "message": "Tarea delegada exitosamente"
}</code></pre>

            <h4>Error de validación (422)</h4>
            <pre><code>{
    "message": "The title field is required.",
    "errors": {
        "title": ["The title field is required."]
    }
}</code></pre>

            <h4>Error de autenticación (401)</h4>
            <pre><code>{
    "message": "Unauthenticated."
}</code></pre>

            <h4>Error de autorización (403)</h4>
            <pre><code>{
    "message": "This action is unauthorized."
}</code></pre>
        </div>

        {{-- ═══════════════ SECURITY ═══════════════ --}}
        <div class="section" id="security">
            <h2>Seguridad</h2>

            <div class="grid-2">
                <div class="card">
                    <div class="card-title">Autenticación</div>
                    <p>Tokens Sanctum con expiración configurable. Usuarios inactivos no pueden hacer login. Rate limiting: 60 req/min.</p>
                </div>
                <div class="card">
                    <div class="card-title">Autorización</div>
                    <p>Policies verifican permisos por modelo. Form Requests validan y autorizan. Un superadmin no puede auto-degradarse.</p>
                </div>
                <div class="card">
                    <div class="card-title">Validación</div>
                    <p>Todos los endpoints usan Form Requests. Passwords: min 8 chars, mayúsculas, minúsculas, números, símbolos.</p>
                </div>
                <div class="card">
                    <div class="card-title">Protección de datos</div>
                    <p>Contraseñas hasheadas con bcrypt. Soft deletes en tareas. Transacciones DB en toda escritura.</p>
                </div>
                <div class="card">
                    <div class="card-title">Auditoría</div>
                    <p>Cada acción relevante en <code>activity_logs</code>. Historial de estados en <code>task_status_history</code>. Delegaciones rastreadas.</p>
                </div>
                <div class="card">
                    <div class="card-title">Middleware</div>
                    <p><code>auth:sanctum</code> en rutas protegidas. Throttle API global. Frontend stateful middleware.</p>
                </div>
            </div>
        </div>

        {{-- ═══════════════ MIGRATIONS ═══════════════ --}}
        <div class="section" id="migrations">
            <h2>Migraciones</h2>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>#</th><th>Tabla</th><th>Descripción</th></tr></thead>
                    <tbody>
                        <tr><td>1</td><td><code>roles</code></td><td>Roles del sistema</td></tr>
                        <tr><td>2</td><td><code>users</code> (mod)</td><td>Agrega <code>role_id</code>, <code>active</code></td></tr>
                        <tr><td>3</td><td><code>areas</code></td><td>Áreas de trabajo</td></tr>
                        <tr><td>4</td><td><code>area_members</code></td><td>Membresías área-usuario</td></tr>
                        <tr><td>5</td><td><code>tasks</code></td><td>Tabla central de tareas</td></tr>
                        <tr><td>6</td><td><code>task_delegations</code></td><td>Historial de delegaciones</td></tr>
                        <tr><td>7</td><td><code>task_comments</code></td><td>Comentarios en tareas</td></tr>
                        <tr><td>8</td><td><code>task_attachments</code></td><td>Archivos adjuntos</td></tr>
                        <tr><td>9</td><td><code>task_notifications</code></td><td>Notificaciones de cumplimiento</td></tr>
                        <tr><td>10</td><td><code>task_status_history</code></td><td>Historial de cambios de estado</td></tr>
                        <tr><td>11</td><td><code>activity_logs</code></td><td>Log de auditoría general</td></tr>
                        <tr><td>12</td><td><code>meetings</code></td><td>Reuniones / origen de compromisos</td></tr>
                        <tr><td>13</td><td><code>tasks</code> (mod)</td><td>Agrega <code>meeting_id</code>, flags de notificación, <code>progress_percent</code></td></tr>
                        <tr><td>14</td><td><code>task_updates</code></td><td>Reportes de avance/seguimiento</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══════════════ TESTS ═══════════════ --}}
        <div class="section" id="tests">
            <h2>Tests</h2>
            <p>80 tests organizados por feature, 187 assertions — todos pasando con SQLite in-memory.</p>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>Suite</th><th>Tests</th><th>Cobertura</th></tr></thead>
                    <tbody>
                        <tr><td><code>AuthTest</code></td><td>5</td><td>Login, logout, perfil, credenciales inválidas, usuario inactivo</td></tr>
                        <tr><td><code>UserTest</code></td><td>7</td><td>CRUD, cambio de rol, activar/desactivar, validaciones</td></tr>
                        <tr><td><code>AreaTest</code></td><td>6</td><td>CRUD, asignar encargado, reclamar trabajador, validaciones</td></tr>
                        <tr><td><code>MeetingTest</code></td><td>10</td><td>CRUD, permisos, vinculación con tareas, filtrado por área</td></tr>
                        <tr><td><code>TaskTest</code></td><td>15</td><td>CRUD, delegación, flujo completo de estados, adjuntos, comentarios</td></tr>
                        <tr><td><code>TaskUpdateTest</code></td><td>6</td><td>Avances, validaciones, permisos, sincronización de progreso</td></tr>
                        <tr><td><code>DashboardTest</code></td><td>7</td><td>Dashboard general, por área, personal, permisos, métricas</td></tr>
                        <tr><td><code>ScheduledCommandsTest</code></td><td>5</td><td>Detección overdue, resumen diario, recordatorios, flags</td></tr>
                    </tbody>
                </table>
            </div>

            <h4>Ejecutar tests</h4>
            <pre><code>php artisan test
php vendor/bin/phpunit --testdox</code></pre>
        </div>

        {{-- Footer --}}
        <div style="border-top:1px solid var(--border); padding-top:24px; margin-top:48px; text-align:center; color:var(--text-muted); font-size:13px;">
            TAPE API v1.0 &middot; Laravel {{ app()->version() }} &middot; PHP {{ PHP_VERSION }}
        </div>

    </main>
</div>
</body>
</html>
