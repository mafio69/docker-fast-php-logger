<?php
declare(strict_types=1);

$directories = [
    'App logs'  => __DIR__ . '/../logs',
    'PHP errors' => dirname(ini_get('error_log') ?: '/var/www/html/logs/php-errors.log'),
    '/var/www/logs' => '/var/www/logs',
    'Home'      => getenv('HOME') ?: '/root',
];

$dirKey = $_GET['dir'] ?? 'App logs';
if (!isset($directories[$dirKey])) $dirKey = 'App logs';
$logsDir = $directories[$dirKey];

$allFiles = [];
foreach (glob($logsDir . '/{*.log,*.php,*/*.log,*/*.php,*/*/*.log,*/*/*.php}', GLOB_BRACE) ?: [] as $file) {
    $rel = substr($file, strlen($logsDir) + 1);
    $allFiles[$rel] = $file;
}
krsort($allFiles);

$today    = date('Y-m-d');
$selectedFile = $_GET['file'] ?? '';
$dateFrom = $_GET['from'] ?? $today;
$dateTo   = $_GET['to']   ?? $today;
if ($dateFrom > $dateTo) [$dateFrom, $dateTo] = [$dateTo, $dateFrom];

function loadLogFile(string $path): array {
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    // Strip Sellasist PHP header
    if ($lines && str_starts_with($lines[0], '<?php')) {
        array_shift($lines);
    }
    return $lines;
}

$lines = [];
$loadedFiles = [];
if ($selectedFile !== '' && isset($allFiles[$selectedFile])) {
    $lines = loadLogFile($allFiles[$selectedFile]);
    $loadedFiles[] = $selectedFile;
} else {
    foreach ($allFiles as $rel => $file) {
        $base = basename($rel, '.log');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/'  , $base) && $base >= $dateFrom && $base <= $dateTo) {
            $lines = array_merge($lines, loadLogFile($file));
            $loadedFiles[] = $rel;
        }
    }
    if (empty($lines) && !empty($allFiles)) {
        $selectedFile = array_key_first($allFiles);
        $lines = loadLogFile($allFiles[$selectedFile]);
        $loadedFiles[] = $selectedFile;
    }
}
usort($lines, fn($a, $b) => strncmp($b, $a, 19));

function parseLine(string $line): array
{
    if (!preg_match('/^\[([^\]]+)\] \[([A-Z]+)\] \[([^\]]*)\] (.*)$/', $line, $m)) {
        return ['raw' => $line];
    }
    $rest = $m[4]; $json = null;
    if (preg_match('/(\{.*\}|\[.*\])$/', $rest, $jm)) {
        $json = $jm[1];
        $rest = trim(substr($rest, 0, -strlen($json)));
    }
    return ['date' => $m[1], 'level' => $m[2], 'location' => $m[3], 'message' => $rest, 'json' => $json];
}

$levelColors = [
    'DEBUG'    => '#6a9fb5',
    'INFO'     => '#90a959',
    'WARNING'  => '#f4bf75',
    'ERROR'    => '#ac4142',
    'CRITICAL' => '#ff5f5f',
];

$dateFiles = array_filter(array_keys($allFiles), fn($r) => preg_match('/\d{4}-\d{2}-\d{2}/', basename($r, '.log')));
$dates = array_map(fn($r) => basename($r, '.log'), $dateFiles);
sort($dates);
$minDate = reset($dates) ?: $today;
$maxDate = end($dates) ?: $today;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Logs</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0d0d0d; color: #c5c8c6; font: 13px/1.5 'Courier New', monospace; display: flex; height: 100vh; overflow: hidden; }

  /* ── Sidebar ── */
  #sidebar { width: 210px; min-width: 210px; background: #111; border-right: 1px solid #2a2a2a; display: flex; flex-direction: column; overflow-y: auto; }
  .s-section { padding: 12px; border-bottom: 1px solid #1e1e1e; }
  .s-label { font-size: 13px; color: #5ba0d0; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; font-weight: 600; }
  .s-row { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 6px; }
  .s-row:last-child { margin-bottom: 0; }
  .s-row span { color: #555; font-size: 11px; white-space: nowrap; }
  .t-input { background: #1a1a1a; border: 1px solid #2a2a2a; color: #c5c8c6; padding: 3px 5px; font: inherit; font-size: 12px; border-radius: 3px; width: 100%; color-scheme: dark; }
  .t-input:focus { outline: none; border-color: #444; }
  .apply-btn { width: 100%; background: #1a2a1a; border: 1px solid #2a4a2a; color: #90a959; padding: 5px; cursor: pointer; font: inherit; font-size: 12px; border-radius: 3px; margin-top: 4px; }
  .apply-btn:hover { background: #1e341e; }

  /* level toggles */
  .lvl-toggle { display: flex; align-items: center; gap: 8px; padding: 4px 6px; border-radius: 3px; cursor: pointer; user-select: none; width: 100%; border: none; background: none; font: inherit; font-size: 12px; text-align: left; }
  .lvl-toggle:hover { background: #1a1a1a; }
  .lvl-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
  .lvl-toggle.off { opacity: .35; }
  .lvl-count { margin-left: auto; color: #444; font-size: 11px; }

  /* sort */
  .sort-btn { width: 100%; background: #1a1a1a; border: 1px solid #2a2a2a; color: #888; padding: 5px 8px; cursor: pointer; font: inherit; font-size: 12px; border-radius: 3px; text-align: left; }
  .sort-btn:hover { border-color: #444; color: #c5c8c6; }

  /* stats */
  #s-count { color: #90a959; font-size: 13px; font-weight: bold; }
  #s-range { color: #444; font-size: 11px; margin-top: 2px; }

  /* ── Log area ── */
  #main { flex: 1; overflow-y: auto; padding: 6px 0; }
  .row { display: flex; gap: 10px; padding: 3px 14px; border-bottom: 1px solid #161616; align-items: baseline; cursor: pointer; }
  .row:hover { background: #141414; }
  .date { color: #555; white-space: nowrap; flex-shrink: 0; }
  .level { font-weight: bold; width: 60px; flex-shrink: 0; text-align: right; }
  .loc { color: #444; font-size: 11px; white-space: nowrap; flex-shrink: 0; max-width: 160px; overflow: hidden; text-overflow: ellipsis; }
  .msg { flex: 1; word-break: break-all; }
  .ctx { color: #555; font-size: 11px; word-break: break-all; }
  .raw { color: #555; padding: 3px 14px; }
  #empty { color: #444; padding: 20px 14px; }

  /* ── Modal ── */
  #modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.75); z-index: 10; align-items: center; justify-content: center; }
  #modal.open { display: flex; }
  #modal-box { background: #111; border: 1px solid #2a2a2a; border-radius: 6px; width: min(700px, 95vw); max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; }
  #modal-head { padding: 12px 16px; border-bottom: 1px solid #2a2a2a; display: flex; justify-content: space-between; align-items: baseline; gap: 12px; }
  #modal-level { font-weight: bold; font-size: 13px; }
  #modal-date { color: #555; font-size: 12px; }
  #modal-close { background: none; border: none; color: #555; font-size: 18px; cursor: pointer; }
  #modal-close:hover { color: #c5c8c6; }
  #modal-body { overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
  #modal-loc { color: #555; font-size: 12px; }
  #modal-msg { font-size: 16px; color: #e0e0e0; word-break: break-word; }
  #modal-json { background: #0d0d0d; border: 1px solid #222; border-radius: 4px; padding: 12px; font-size: 13px; overflow-x: auto; white-space: pre; }
</style>
</head>
<body>

<div id="sidebar">

  <div class="s-section">
    <div class="s-label">Katalog</div>
    <select id="f-dir" class="t-input" onchange="applyDir(this.value)">
      <?php foreach ($directories as $name => $path): ?>
      <option value="<?= htmlspecialchars($name) ?>"<?= $dirKey === $name ? " selected" : "" ?>><?= htmlspecialchars($name) ?> (<?= $path ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="s-section">
    <div class="s-label">Plik</div>
    <select id="f-file" class="t-input" onchange="applyFile(this.value)">
      <option value="">— wybierz —</option>
      <?php foreach ($allFiles as $rel => $path): ?>
      <option value="<?= htmlspecialchars($rel) ?>"<?= $selectedFile === $rel ? ' selected' : '' ?>><?= htmlspecialchars($rel) ?> (<?= number_format(filesize($path) / 1024, 1) ?> KB)</option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="s-section">
    <div class="s-label">Zakres dat</div>
    <div class="s-row"><span>Od</span><input type="date" id="f-from" class="t-input" value="<?= $dateFrom ?>" min="<?= $minDate ?>" max="<?= $maxDate ?>"></div>
    <div class="s-row"><span>Do</span><input type="date" id="f-to"   class="t-input" value="<?= $dateTo ?>"   min="<?= $minDate ?>" max="<?= $maxDate ?>"></div>
    <button class="apply-btn" onclick="applyDates()">Zastosuj</button>
  </div>

  <div class="s-section">
    <div class="s-label">Poziomy</div>
    <?php foreach ($levelColors as $lvl => $col): ?>
    <button class="lvl-toggle" data-level="<?= $lvl ?>" onclick="toggleLevel(this)">
      <span class="lvl-dot" style="background:<?= $col ?>"></span>
      <span style="color:<?= $col ?>"><?= $lvl ?></span>
      <span class="lvl-count" id="cnt-<?= $lvl ?>">0</span>
    </button>
    <?php endforeach; ?>
  </div>

  <div class="s-section">
    <div class="s-label">Godziny</div>
    <div class="s-row"><span>Od</span><input type="time" id="f-time-from" class="t-input" oninput="update()"></div>
    <div class="s-row"><span>Do</span><input type="time" id="f-time-to"   class="t-input" oninput="update()"></div>
  </div>

  <div class="s-section">
    <div class="s-label">Sortowanie</div>
    <button class="sort-btn" id="sort-btn" onclick="toggleSort()">↓ Newest first</button>
  </div>

  <div class="s-section" style="margin-top:auto;border-top:1px solid #1e1e1e">
    <div id="s-count">—</div>
    <div id="s-range"><?= $dateFrom === $dateTo ? $dateFrom : "$dateFrom → $dateTo" ?></div>
  </div>

</div>

<div id="main">
<?php if ($loadedFiles): ?><div style="padding:6px 12px;background:#23241f;color:#aaa;font-size:12px;">📄 <?= htmlspecialchars(implode(', ', $loadedFiles)) ?> (<?= count($lines) ?> wpisów)</div><?php endif; ?>
  <?php if (empty($lines)): ?>
    <div id="empty">Brak wpisów dla wybranego zakresu.</div>
  <?php else: foreach ($lines as $line):
      $p = parseLine($line); ?>
    <?php if (isset($p['raw'])): ?>
      <div class="raw"><?= htmlspecialchars($p['raw']) ?></div>
    <?php else: ?>
      <div class="row" data-level="<?= $p['level'] ?>" data-ts="<?= strtotime($p['date']) ?>" style="--c:<?= $levelColors[$p['level']] ?? '#c5c8c6' ?>">
        <span class="date"><?= htmlspecialchars($p['date']) ?></span>
        <span class="level" style="color:var(--c)"><?= $p['level'] ?></span>
        <span class="loc" title="<?= htmlspecialchars($p['location']) ?>"><?= htmlspecialchars($p['location']) ?></span>
        <span class="msg" style="color:var(--c)"><?= htmlspecialchars($p['message']) ?></span>
        <?php if ($p['json']): ?><span class="ctx"><?= htmlspecialchars($p['json']) ?></span><?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endforeach; endif; ?>
</div>

<div id="modal" role="dialog" aria-modal="true">
  <div id="modal-box">
    <div id="modal-head">
      <span id="modal-level"></span>
      <span id="modal-date"></span>
      <button id="modal-close" aria-label="Close">✕</button>
    </div>
    <div id="modal-body">
      <div id="modal-loc"></div>
      <div id="modal-msg"></div>
      <pre id="modal-json" style="display:none"></pre>
    </div>
  </div>
</div>

<script>
const rows = [...document.querySelectorAll('.row[data-level]')];
const main = document.getElementById('main');
const levelColors = <?= json_encode($levelColors) ?>;
let sortDesc = true;

function tsOf(dateVal, timeVal, end = false) {
  if (!dateVal || !timeVal) return null;
  return new Date(dateVal + 'T' + timeVal + (end ? ':59' : ':00')).getTime() / 1000;
}

function update() {
  const active = new Set([...document.querySelectorAll('.lvl-toggle:not(.off)')].map(b => b.dataset.level));
  const from = tsOf(document.getElementById('f-from').value, document.getElementById('f-time-from').value);
  const to   = tsOf(document.getElementById('f-to').value,   document.getElementById('f-time-to').value, true);

  // reset counts
  Object.keys(levelColors).forEach(l => document.getElementById('cnt-' + l).textContent = '0');

  let n = 0;
  const counts = {};
  rows.forEach(r => {
    const ts   = parseInt(r.dataset.ts, 10);
    const lvl  = r.dataset.level;
    const show = active.has(lvl)
      && (from === null || ts >= from)
      && (to   === null || ts <= to);
    r.style.display = show ? '' : 'none';
    if (show) { n++; counts[lvl] = (counts[lvl] || 0) + 1; }
  });

  Object.entries(counts).forEach(([l, c]) => {
    const el = document.getElementById('cnt-' + l);
    if (el) el.textContent = c;
  });
  document.getElementById('s-count').textContent = n + ' entries';
}

function toggleLevel(btn) {
  btn.classList.toggle('off');
  update();
}

function toggleSort() {
  sortDesc = !sortDesc;
  document.getElementById('sort-btn').textContent = sortDesc ? '↓ Newest first' : '↑ Oldest first';
  const sorted = [...rows].sort((a, b) => sortDesc
    ? parseInt(b.dataset.ts) - parseInt(a.dataset.ts)
    : parseInt(a.dataset.ts) - parseInt(b.dataset.ts)
  );
  sorted.forEach(r => main.appendChild(r));
}

function applyDir(val) {
  location.href = "?dir=" + encodeURIComponent(val);
}

function applyFile(val) {
  const dir = document.getElementById("f-dir").value;
  const base = "?dir=" + encodeURIComponent(dir);
  location.href = val ? base + "&file=" + val : base;
}

function applyDates() {
  const from = document.getElementById('f-from').value;
  const to   = document.getElementById('f-to').value;
  const dir = document.getElementById("f-dir").value;
  if (from) location.href = '?dir=' + encodeURIComponent(dir) + '&from=' + from + '&to=' + (to || from);
}

update();

// Modal
rows.forEach(row => {
  row.addEventListener('click', () => {
    const level = row.dataset.level;
    document.getElementById('modal-level').textContent = level;
    document.getElementById('modal-level').style.color = levelColors[level] || '#c5c8c6';
    document.getElementById('modal-date').textContent  = row.querySelector('.date').textContent;
    document.getElementById('modal-loc').textContent   = row.querySelector('.loc').title || row.querySelector('.loc').textContent;
    document.getElementById('modal-msg').textContent   = row.querySelector('.msg').textContent;
    const ctxEl  = row.querySelector('.ctx');
    const jsonEl = document.getElementById('modal-json');
    if (ctxEl) {
      try { jsonEl.textContent = JSON.stringify(JSON.parse(ctxEl.textContent), null, 2); }
      catch { jsonEl.textContent = ctxEl.textContent; }
      jsonEl.style.display = '';
    } else {
      jsonEl.style.display = 'none';
    }
    document.getElementById('modal').classList.add('open');
  });
});

const modal = document.getElementById('modal');
document.getElementById('modal-close').addEventListener('click', () => modal.classList.remove('open'));
modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('open'); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') modal.classList.remove('open'); });
</script>
</body>
</html>
