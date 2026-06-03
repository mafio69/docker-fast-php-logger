<?php

declare(strict_types=1);

require_once __DIR__ . '/shared/bootstrap.php';
require_once __DIR__ . '/shared/JsonResponse.php';
require_once __DIR__ . '/shared/ConfigStore.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Mariusz\Logger\DualLogger;
use Psr\Log\LogLevel;

// ── Config persistence ────────────────────────────────────────────────────────
$configStore = new ConfigStore(__DIR__ . '/../config.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    try {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? throw new RuntimeException('Invalid JSON input');
        $config = $configStore->load();

        match ($_GET['action']) {
            'save_hosts' => $config['hosts'] = $input,
            'save_app' => $config['app'] = $input,
            default => throw new RuntimeException("Unknown action: {$_GET['action']}"),
        };

        $configStore->save($config);
        JsonResponse::send(['ok' => true]);
    } catch (Throwable $e) {
        JsonResponse::error($e->getMessage(), 500, [
            'file' => basename($e->getFile()) . ':' . $e->getLine(),
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'config') {
    try {
        JsonResponse::send($configStore->load());
    } catch (Throwable $e) {
        JsonResponse::error($e->getMessage(), 500);
    }
    exit;
}

// ── Logger demo ───────────────────────────────────────────────────────────────
$loggerError = null;
try {
    $logger = DualLogger::create(
        logDir: __DIR__ . '/../logs',
        minLevel: LogLevel::DEBUG,
        timezone: $_ENV['APP_TIMEZONE'] ?? 'Europe/Warsaw',
    );
    $logger->debug('Dashboard loaded', ['php' => PHP_VERSION]);
} catch (Throwable $e) {
    $loggerError = $e->getMessage();
}

// ── DB check ──────────────────────────────────────────────────────────────────
$dbStatus = 'not connected';
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'] ?? 'db',
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_DATABASE'] ?? 'app',
        ),
        $_ENV['DB_USERNAME'] ?? 'app',
        $_ENV['DB_PASSWORD'] ?? 'secret',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
    $dbStatus = 'connected';
} catch (\PDOException $e) {
    $dbStatus = 'error: ' . $e->getMessage();
}

$dbOk = str_starts_with($dbStatus, 'connected');
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ fast-php-logger — dashboard</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0a0a0a; color:#a0a0a0; font-family:'JetBrains Mono','Fira Code','Cascadia Code',monospace; font-size:13px; min-height:100vh; }
        ::selection { background:#264f78; color:#fff; }
        ::-webkit-scrollbar { width:6px; } ::-webkit-scrollbar-track { background:#111; } ::-webkit-scrollbar-thumb { background:#333; border-radius:3px; }

        .shell { max-width:1100px; margin:0 auto; padding:24px 20px; }
        .prompt { color:#4ec9b0; } .prompt::before { content:'❯ '; color:#569cd6; }
        .header { border-bottom:1px solid #222; padding-bottom:16px; margin-bottom:20px; }
        .header h1 { font-size:15px; font-weight:600; color:#e0e0e0; margin-bottom:4px; }
        .header .sub { color:#555; font-size:11px; }

        .grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
        @media(max-width:768px) { .grid { grid-template-columns:1fr; } }

        .card { background:#111; border:1px solid #222; border-radius:6px; overflow:hidden; }
        .card-head { background:#161616; padding:8px 12px; border-bottom:1px solid #222; display:flex; align-items:center; gap:8px; }
        .card-head .dot { width:8px; height:8px; border-radius:50%; }
        .card-head .title { font-size:11px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:.5px; }
        .card-body { padding:12px; }

        .stat { display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid #1a1a1a; }
        .stat:last-child { border:none; }
        .stat .key { color:#666; }
        .stat .val { color:#e0e0e0; }
        .stat .val.ok { color:#4ec9b0; }
        .stat .val.err { color:#f44747; }

        .nav-list { list-style:none; }
        .nav-list li { padding:6px 0; border-bottom:1px solid #1a1a1a; }
        .nav-list li:last-child { border:none; }
        .nav-list a { color:#569cd6; text-decoration:none; }
        .nav-list a:hover { color:#4ec9b0; text-decoration:underline; }
        .nav-list .desc { color:#555; margin-left:8px; }

        .panel { margin-bottom:20px; }
        .panel-title { font-size:12px; color:#4ec9b0; margin-bottom:10px; padding:6px 0; border-bottom:1px solid #222; }
        .panel-title::before { content:'┌─ '; color:#333; }

        .form-row { display:flex; gap:8px; align-items:center; margin-bottom:6px; flex-wrap:wrap; }
        .form-row label { color:#666; font-size:11px; min-width:60px; }
        input[type="text"], select { background:#0d0d0d; border:1px solid #2a2a2a; color:#e0e0e0; padding:5px 8px; border-radius:3px; font-family:inherit; font-size:12px; }
        input[type="text"] { flex:1; min-width:120px; }
        input[type="text"]:focus, select:focus { outline:none; border-color:#4ec9b0; }
        select { padding:4px 6px; }

        .cb { display:flex; align-items:center; gap:4px; cursor:pointer; }
        .cb input { accent-color:#4ec9b0; }
        .cb span { color:#888; font-size:11px; }

        .btn { background:#1a1a1a; border:1px solid #333; color:#4ec9b0; padding:5px 12px; border-radius:3px; cursor:pointer; font-family:inherit; font-size:11px; }
        .btn:hover { background:#222; border-color:#4ec9b0; }
        .btn-add { color:#569cd6; border-color:#264f78; }
        .btn-del { color:#f44747; border-color:#3a1a1a; padding:4px 8px; }
        .btn-del:hover { background:#2a0a0a; }

        .host-row { display:grid; grid-template-columns:1fr 80px 80px 60px 30px; gap:6px; align-items:center; margin-bottom:6px; padding:6px 8px; background:#0d0d0d; border:1px solid #1a1a1a; border-radius:3px; }
        .host-row input[type="text"] { min-width:auto; }

        .toast { position:fixed; bottom:20px; right:20px; background:#1a3a2a; border:1px solid #4ec9b0; color:#4ec9b0; padding:8px 16px; border-radius:4px; font-size:11px; opacity:0; transition:opacity .3s; }
        .toast.show { opacity:1; }

        .footer { margin-top:30px; padding-top:12px; border-top:1px solid #1a1a1a; color:#333; font-size:11px; text-align:center; }
    </style>
</head>
<body>
<div id="app" class="shell">

    <!-- Header -->
    <div class="header">
        <h1><span class="prompt">docker-fast-php-logger</span></h1>
        <div class="sub">development dashboard · <?= date('Y-m-d H:i') ?></div>
    </div>

    <!-- Status cards -->
    <div class="grid">
        <div class="card">
            <div class="card-head">
                <span class="dot" style="background:#4ec9b0"></span>
                <span class="title">System</span>
            </div>
            <div class="card-body">
                <div class="stat"><span class="key">PHP</span><span class="val ok"><?= PHP_VERSION ?></span></div>
                <div class="stat"><span class="key">ENV</span><span class="val"><?= htmlspecialchars($_ENV['APP_ENV'] ?? 'unknown') ?></span></div>
                <div class="stat"><span class="key">DB</span><span class="val <?= $dbOk ? 'ok' : 'err' ?>"><?= htmlspecialchars($dbStatus) ?></span></div>
                <div class="stat"><span class="key">Logger</span><span class="val <?= $loggerError ? 'err' : 'ok' ?>"><?= $loggerError ? htmlspecialchars($loggerError) : 'ok' ?></span></div>
                <div class="stat"><span class="key">Logs</span><span class="val">/var/www/html/logs</span></div>
            </div>
        </div>
        <div class="card">
            <div class="card-head">
                <span class="dot" style="background:#569cd6"></span>
                <span class="title">Nawigacja</span>
            </div>
            <div class="card-body">
                <ul class="nav-list">
                    <li><a href="http://logs.local">📋 Log Viewer</a><span class="desc">przeglądarka logów</span></li>
                    <li><a href="http://pma.local">🗄️ phpMyAdmin</a><span class="desc">zarządzanie bazą</span></li>
                    <li><a href="http://adminer.local">⚡ Adminer</a><span class="desc">lekki DB manager</span></li>
                    <li><a href="http://mail.local">📧 Mailpit</a><span class="desc">przechwycone maile</span></li>
                    <li><a href="http://portainer.local">🐳 Portainer</a><span class="desc">kontenery</span></li>
                    <li><a href="http://mdviewer.local">📖 Docs</a><span class="desc">dokumentacja MD</span></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- HTTP Server Config -->
    <div class="panel">
        <div class="panel-title">HTTP Server — mapowania domen</div>
        <div class="card">
            <div class="card-body">
                <div v-for="(h, i) in hosts" :key="i" class="host-row">
                    <input type="text" v-model="h.domain" placeholder="nazwa (np. api)">
                    <select v-model="h.suffix">
                        <option value=".local">.local</option>
                        <option value=".test">.test</option>
                        <option value=".dev">.dev</option>
                    </select>
                    <input type="text" v-model="h.port" placeholder="port">
                    <label class="cb"><input type="checkbox" v-model="h.ssl"><span>SSL</span></label>
                    <button class="btn btn-del" @click="hosts.splice(i,1)">×</button>
                </div>
                <div class="form-row" style="margin-top:8px;">
                    <button class="btn btn-add" @click="hosts.push({domain:'',suffix:'.local',port:'',ssl:false})">+ Dodaj</button>
                    <button class="btn" @click="saveHosts">Zapisz</button>
                </div>
            </div>
        </div>
    </div>

    <!-- App Config -->
    <div class="panel">
        <div class="panel-title">Konfiguracja aplikacji</div>
        <div class="card">
            <div class="card-body">
                <div class="form-row">
                    <label>LOG_DIR</label>
                    <input type="text" v-model="appCfg.log_dir" placeholder="/var/www/html/logs">
                </div>
                <div class="form-row">
                    <label>EDITOR_URL</label>
                    <input type="text" v-model="appCfg.editor_url" placeholder="phpstorm://open?file={file}&line={line}">
                </div>
                <div class="form-row">
                    <label>TIMEZONE</label>
                    <input type="text" v-model="appCfg.timezone" placeholder="Europe/Warsaw">
                </div>
                <div class="form-row">
                    <label>LOG_LEVEL</label>
                    <select v-model="appCfg.log_level">
                        <option v-for="l in logLevels" :value="l">{{ l }}</option>
                    </select>
                </div>
                <div class="form-row" style="margin-top:8px;">
                    <button class="btn" @click="saveApp">Zapisz</button>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">fast-php-* suite · {{ new Date().getFullYear() }}</div>
    <div class="toast" :class="{show: toast}">{{ toastMsg }}</div>
</div>

<script>
const {createApp, ref, reactive, onMounted} = Vue;
createApp({
    setup() {
        const hosts = ref([]);
        const appCfg = reactive({log_dir:'', editor_url:'', timezone:'Europe/Warsaw', log_level:'DEBUG'});
        const logLevels = ['DEBUG','INFO','NOTICE','WARNING','ERROR','CRITICAL','ALERT','EMERGENCY'];
        const toast = ref(false);
        const toastMsg = ref('');

        function notify(msg) {
            toastMsg.value = msg; toast.value = true;
            setTimeout(() => toast.value = false, 2000);
        }

        async function loadConfig() {
            const r = await fetch('?action=config');
            const cfg = await r.json();
            if (cfg.hosts) hosts.value = cfg.hosts;
            if (cfg.app) Object.assign(appCfg, cfg.app);
        }

        async function saveHosts() {
            await fetch('?action=save_hosts', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(hosts.value)});
            notify('✓ Hosts saved');
        }

        async function saveApp() {
            await fetch('?action=save_app', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(appCfg)});
            notify('✓ App config saved');
        }

        onMounted(loadConfig);
        return {hosts, appCfg, logLevels, toast, toastMsg, saveHosts, saveApp};
    }
}).mount('#app');
</script>
</body>
</html>
