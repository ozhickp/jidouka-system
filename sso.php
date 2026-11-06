<?php
/**
 * One YADIN SSO gate for jidouka-system (jidouka-system.yadin.com).
 *
 * Auto-prepended (via .htaccess) to every request, so the app's inline
 * $_SESSION checks need no central auth file. Derives the app's legacy session
 * from the shared portal session + a grant on system 34:
 *   - everyone granted        -> employee session $_SESSION['jd_user'] (the user row)
 *   - admin / superadmin tier -> ALSO the admin session $_SESSION['admin_id']
 * The app's own 'user' key was renamed to 'jd_user' so we never clobber the
 * portal's $_SESSION['user'].
 *
 * PUBLIC PATHS: the scripts in JD_PUBLIC_PATHS are reachable with NO portal
 * session at all — an anonymous visitor renders them instead of being bounced
 * to the portal login. The gate still runs for those paths when a session IS
 * present, so a signed-in user is provisioned normally and the page can route
 * them by role. Keep the list as small as possible: anything added here is
 * world-readable to anyone who can reach the host.
 */

if (defined('JD_SSO_RAN')) return;
define('JD_SSO_RAN', 1);
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

const JD_SYSTEM_ID = 34;

// Reachable without a portal session.
//   index.php               — sends any signed-in user to dashboard_admin;
//                             renders the read-only monitoring view in place
//                             for anonymous visitors, keeping the /index URL.
//   get_machine_data2.php   — read-only JSON that view polls.
//   get_conveyor_status.php — read-only JSON that view polls.
// Both endpoints were checked: no INSERT/UPDATE/DELETE, no $_SESSION use —
// they only read machine state.
// NOTE monitoring_public.php is deliberately NOT listed. index.php includes it,
// and an include does not re-trigger this auto-prepended gate, so the view
// renders inside /index while its own URL stays gated. Add it here if you ever
// want a direct TV/kiosk URL as well.
const JD_PUBLIC_PATHS = [
    'index.php',
    'get_machine_data2.php',
    'get_conveyor_status.php',
];

// SCRIPT_NAME, not REQUEST_URI: the .htaccess clean-url rewrite turns /index
// into /index.php, and SCRIPT_NAME reflects the resolved file. Using the URI
// would let '/index?x=' or odd casing slip past the comparison.
$jd_public = in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), JD_PUBLIC_PATHS, true);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
$onBase = ($host === 'yadin.com' || substr($host, -10) === '.yadin.com');
$portal = $onBase ? "$scheme://yadin.com" : "$scheme://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/one-yadin";
$self   = "$scheme://" . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');

$jd_deny = function (string $reason) use ($portal, $self) {
    foreach (['jd_user', 'admin_id', 'admin_username'] as $k) unset($_SESSION[$k]);
    header('Location: ' . $portal . ($reason === 'nogrant' ? '/systems' : '/login?return=' . urlencode($self)));
    exit;
};

$u = $_SESSION['user'] ?? null;           // the PORTAL's key
if (!$u || !isset($u['id'])) {
    if ($jd_public) {
        // Clear any stale app-session keys so a half-expired session can't
        // leave the page thinking someone is signed in, then hand control to
        // the page itself. `return` exits only this prepended file.
        foreach (['jd_user', 'admin_id', 'admin_username'] as $k) unset($_SESSION[$k]);
        return;
    }
    $jd_deny('anon');
}

$sock = file_exists('/opt/lampp/var/mysql/mysql.sock') ? '/opt/lampp/var/mysql/mysql.sock' : null;
$oy = @new mysqli('localhost', 'root', '', 'one_yadin', 3306, $sock);
if ($oy->connect_errno) $jd_deny('anon');

// tier for jidouka
$st = $oy->prepare('SELECT role FROM system_access_roles WHERE user_id = ? AND system_id = ?');
$sys = JD_SYSTEM_ID;
$st->bind_param('ii', $u['id'], $sys);
$st->execute();
$roles = [];
$rr = $st->get_result();
while ($x = $rr->fetch_assoc()) $roles[] = $x['role'];
$tier = null;
foreach (['superadmin', 'admin', 'user'] as $p) { if (in_array($p, $roles, true)) { $tier = $p; break; } }
if ($tier === null) {
    // Signed into the portal but not granted Jidouka. On a public path let them
    // see the public view rather than bouncing them to /systems.
    if ($jd_public) {
        foreach (['jd_user', 'admin_id', 'admin_username'] as $k) unset($_SESSION[$k]);
        return;
    }
    $jd_deny('nogrant');
}

$emp  = (string) ($u['employee_number'] ?? $u['id']);
$name = (string) ($u['name'] ?? $emp);

$inv = @new mysqli('localhost', 'root', '', 'db_maintenance', 3306, $sock);
if ($inv->connect_errno) $jd_deny('anon');

// provision / fetch the employee (user) row, keyed by empl_num = employee number
$g = $inv->prepare('SELECT id, name, empl_num, division, created_at FROM user WHERE empl_num = ? LIMIT 1');
$g->bind_param('s', $emp);
$g->execute();
$row = $g->get_result()->fetch_assoc();
if ($row) {
    // Keep the employee name in sync with the hub (source of truth).
    if ($name !== '' && ($row['name'] ?? '') !== $name) {
        $up = $inv->prepare('UPDATE user SET name = ? WHERE id = ?');
        $up->bind_param('si', $name, $row['id']);
        $up->execute();
        $row['name'] = $name;
    }
} else {
    // division from the Employee Master (best effort)
    $div = '';
    if ($dq = $inv->prepare("SELECT dv.division_name FROM employee_masters.employee e
                              LEFT JOIN employee_masters.department d ON d.department_id = e.department_id
                              LEFT JOIN employee_masters.division dv ON dv.division_id = d.division_id
                             WHERE e.employee_number = ? LIMIT 1")) {
        $dq->bind_param('s', $emp);
        $dq->execute();
        $div = $dq->get_result()->fetch_assoc()['division_name'] ?? '';
    }
    $ins = $inv->prepare('INSERT INTO user (name, empl_num, division, created_at) VALUES (?, ?, ?, NOW())');
    $ins->bind_param('sss', $name, $emp, $div);
    $ins->execute();
    $g->execute();
    $row = $g->get_result()->fetch_assoc();
}
$_SESSION['jd_user'] = $row;

// admin / superadmin also get the admin backend session
if ($tier === 'admin' || $tier === 'superadmin') {
    $a = $inv->prepare('SELECT id FROM admin WHERE username = ? LIMIT 1');
    $a->bind_param('s', $emp);
    $a->execute();
    $arow = $a->get_result()->fetch_assoc();
    if ($arow) {
        $aid = (int) $arow['id'];
    } else {
        $ia = $inv->prepare("INSERT INTO admin (username, password) VALUES (?, '')");
        $ia->bind_param('s', $emp);
        $ia->execute();
        $aid = (int) $inv->insert_id;
    }
    $_SESSION['admin_id']       = $aid;
    $_SESSION['admin_username'] = $emp;
} else {
    unset($_SESSION['admin_id'], $_SESSION['admin_username']);
}
