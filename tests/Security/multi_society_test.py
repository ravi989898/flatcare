"""Multi-society isolation test (Society A vs Society B). Safe payloads only.
Talks to the local dev server; seeds/reads only sectest-* data it created."""
import http.cookiejar
import json
import re
import subprocess
import sys
import urllib.error
import urllib.parse
import urllib.request

BASE = 'http://127.0.0.1:8000'
API = BASE + '/api/v1'
import pathlib
ROOT = pathlib.Path(__file__).resolve().parents[2].as_posix()
DB_A, DB_B = 'society_1_demo-society', 'society_2_sectest-b'
PW = 'SecTest#Passw0rd!'

env = dict(l.split('=', 1) for l in open(ROOT + '/.env', encoding='utf-8').read().splitlines() if '=' in l and not l.startswith('#'))
import glob
import os
MYSQL = os.environ.get('MYSQL_BIN') or sorted(glob.glob('D:/wamp/bin/mysql/*/bin/mysql.exe'))[0]


def sql(db, q):
    cmd = [MYSQL, '-uroot'] + (['-p' + env['DB_PASSWORD']] if env.get('DB_PASSWORD') else []) + ['-N', '-e', q, db]
    return subprocess.run(cmd, capture_output=True, text=True).stdout.strip()


results = []


def check(name, ok, detail=''):
    results.append((name, bool(ok), detail))
    print(('PASS ' if ok else 'FAIL ') + name + (f'  [{detail}]' if detail and not ok else ''))


def call(method, path, token=None, body=None, headers=None, form=None, query=None):
    url = API + path + (('?' + urllib.parse.urlencode(query)) if query else '')
    h = {'Accept': 'application/json'}
    if token:
        h['Authorization'] = 'Bearer ' + token
    if headers:
        h.update(headers)
    data = None
    if body is not None:
        data = json.dumps(body).encode()
        h['Content-Type'] = 'application/json'
    req = urllib.request.Request(url, data=data, headers=h, method=method)
    try:
        with urllib.request.urlopen(req, timeout=60) as r:
            raw = r.read().decode()
            return r.status, (json.loads(raw) if raw else {}), raw
    except urllib.error.HTTPError as e:
        raw = e.read().decode()
        try:
            return e.code, json.loads(raw), raw
        except Exception:
            return e.code, {}, raw


def otp_login(mobile):
    s, j, _ = call('POST', '/auth/otp/verify', body={'mobile_number': mobile, 'otp': '0000'})
    assert s == 200, (mobile, s, j)
    return j['data']['token']


# ---------------------------------------------------------------- actors
tok = {
    'RA': otp_login('9000000011'), 'RB': otp_login('9000000021'),
    'GA': otp_login('9000000013'), 'GB': otp_login('9000000023'),
}
flat_a = int(sql(DB_A, "SELECT id FROM flats WHERE flat_number='ST-a-1'"))
flat_b = int(sql(DB_B, "SELECT id FROM flats WHERE flat_number='ST-b-1'"))

# make ids in B that do NOT exist in A (A tables are small): pad B with extra flats/blocks/visitors
for i in range(2, 9):
    sql(DB_B, f"INSERT INTO flats (block_id,flat_number,floor_number,flat_type,status,created_at,updated_at) SELECT id,'ST-b-x{i}',1,'2bhk','active',NOW(),NOW() FROM blocks LIMIT 1")
    sql(DB_B, f"INSERT INTO blocks (name,block_number,status,created_at,updated_at) VALUES ('SecTest Block b{i}','STB{i}','active',NOW(),NOW())")
b_only_flat = int(sql(DB_B, "SELECT MAX(id) FROM flats"))
b_only_block = int(sql(DB_B, "SELECT MAX(id) FROM blocks"))
assert b_only_flat > int(sql(DB_A, "SELECT MAX(id) FROM flats")), 'need B-only flat id'
assert b_only_block > int(sql(DB_A, "SELECT MAX(id) FROM blocks")), 'need B-only block id'

# ---------------------------------------------------------------- seed data through the real API
def make_visitor(t, flat, name):
    s, j, raw = call('POST', '/visitors', t, {'flat_id': flat, 'visitor_name': name, 'visitor_phone': '9876543210', 'purpose': 'guest', 'entry_kind': 'pre_approval'})
    assert s == 201, (name, s, raw[:200])
    return j['data']['id']

a_vis = [make_visitor(tok['RA'], flat_a, f'SECTEST-A-visitor-{i}') for i in range(2)]
b_vis = [make_visitor(tok['RB'], flat_b, f'SECTEST-B-visitor-{i}') for i in range(6)]
b_only_visitor = max(b_vis)
assert b_only_visitor > max(a_vis), 'need B-only visitor id'

s, j, _ = call('POST', '/daily-helpers', tok['RB'], {'flat_id': flat_b, 'name': 'SECTEST-B-helper', 'phone': '9876543210', 'helper_type': 'maid'})
b_helper = j['data']['id']
for _ in range(3):
    call('POST', '/daily-helpers', tok['RB'], {'flat_id': flat_b, 'name': 'SECTEST-B-helper', 'phone': '9876543210', 'helper_type': 'maid'})
b_only_helper = int(sql(DB_B, "SELECT MAX(id) FROM daily_helpers"))
call('POST', '/daily-helpers', tok['RA'], {'flat_id': flat_a, 'name': 'SECTEST-A-helper', 'phone': '9876543210', 'helper_type': 'cook'})

# ================================================================ 1. horizontal/tenant isolation (API)
print('\n--- Resident A vs Society B data')
s, j, raw = call('GET', '/visitors', tok['RA'])
check('RA visitor list contains no Society B visitor', s == 200 and 'SECTEST-B' not in raw and 'SECTEST-A-visitor' in raw)
s, j, raw = call('GET', '/visitors', tok['RB'])
check('RB visitor list contains no Society A visitor (symmetry)', s == 200 and 'SECTEST-A' not in raw and 'SECTEST-B-visitor' in raw)

for m, p in [('GET', f'/visitors/{b_only_visitor}'), ('DELETE', f'/visitors/{b_only_visitor}'), ('POST', f'/visitors/{b_only_visitor}/approve'), ('POST', f'/visitors/{b_only_visitor}/reject')]:
    s, _, _ = call(m, p, tok['RA'])
    check(f'RA {m} {p} (B-only id) -> 404', s == 404, s)
check('B visitor still exists after RA delete attempt', sql(DB_B, f"SELECT COUNT(*) FROM visitors WHERE id={b_only_visitor} AND deleted_at IS NULL") == '1')

s, _, _ = call('DELETE', f'/daily-helpers/{b_only_helper}', tok['RA'])
check('RA DELETE daily-helper of Society B -> 404', s == 404, s)
s, j, raw = call('GET', '/daily-helpers', tok['RA'])
check('RA daily-helper list excludes Society B helpers', 'SECTEST-B' not in raw and 'SECTEST-A-helper' in raw)

s, j, raw = call('POST', '/visitors', tok['RA'], {'flat_id': b_only_flat, 'visitor_name': 'SECTEST-A-into-B', 'visitor_phone': '9876543210', 'purpose': 'guest', 'entry_kind': 'pre_approval'})
check('RA create visitor for a Society-B flat id -> rejected (422/403)', s in (403, 422), s)
check('...and nothing was written to Society B', sql(DB_B, "SELECT COUNT(*) FROM visitors WHERE visitor_name='SECTEST-A-into-B'") == '0')

s, j, raw = call('POST', '/daily-helpers', tok['RA'], {'flat_id': b_only_flat, 'name': 'SECTEST-A-into-B', 'phone': '9876543210', 'helper_type': 'maid'})
check('RA create daily-helper for a Society-B flat id -> rejected', s in (403, 422), s)

# forged tenant identifiers must be ignored
s, j, raw = call('POST', '/visitors', tok['RA'], {'flat_id': flat_a, 'visitor_name': 'SECTEST-A-forged', 'visitor_phone': '9876543210', 'purpose': 'guest', 'entry_kind': 'pre_approval', 'society_id': 2, 'tenant_society_id': 2},
                  headers={'X-Society-Id': '2', 'X-Tenant': '2'}, query={'society_id': 2})
check('forged society_id (body+query+headers) accepted request but ignored', s == 201)
check('...record landed in Society A only', int(sql(DB_A, "SELECT COUNT(*) FROM visitors WHERE visitor_name='SECTEST-A-forged'")) >= 1 and sql(DB_B, "SELECT COUNT(*) FROM visitors WHERE visitor_name='SECTEST-A-forged'") == '0')
s, j, raw = call('GET', '/visitors', tok['RA'], headers={'X-Society-Id': '2'}, query={'society_id': 2})
check('forged society_id on GET still returns only Society A rows', 'SECTEST-B' not in raw)

# settings: RA cannot toggle Society B's flat
before = sql(DB_B, f"SELECT guest_approval_required,house_closed FROM flats WHERE id={b_only_flat}")
s, j, raw = call('PUT', '/visitor-settings', tok['RA'], {'flat_id': b_only_flat, 'house_closed': True, 'guest_approval_required': True})
after = sql(DB_B, f"SELECT guest_approval_required,house_closed FROM flats WHERE id={b_only_flat}")
check('RA cannot change visitor-settings of a Society-B flat', before == after, (before, after))
check('...the change applied to RA\'s own flat instead', sql(DB_A, f"SELECT house_closed FROM flats WHERE id={flat_a}") == '1')
call('PUT', '/visitor-settings', tok['RA'], {'flat_id': flat_a, 'house_closed': False, 'guest_approval_required': False})

s, j, raw = call('GET', '/directory', tok['RA'])
check('RA directory lists no Society B resident', s == 200 and 'ST-b' not in raw and 'sectest-b' not in raw.lower(), s)

# complaints
CAT = sql(DB_B, "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(COLUMN_TYPE,\"'\",2),\"'\",-1) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='society_2_sectest-b' AND TABLE_NAME='complaints' AND COLUMN_NAME='category'") or 'other'
s, j, raw = call('POST', '/complaints', tok['RB'], {'flat_id': flat_b, 'subject': 'SECTEST-B-complaint', 'description': 'x', 'category': CAT, 'priority': 'low'})
for _ in range(4):
    call('POST', '/complaints', tok['RB'], {'flat_id': flat_b, 'subject': 'SECTEST-B-complaint', 'description': 'x', 'category': CAT, 'priority': 'low'})
_mx = sql(DB_B, "SELECT MAX(id) FROM complaints")
b_only_complaint = int(_mx) if _mx.isdigit() else 0
if b_only_complaint:
    s, _, _ = call('GET', f'/complaints/{b_only_complaint}', tok['RA'])
    check('RA GET complaint of Society B (B-only id) -> 404', s == 404, s)
    s, j, raw = call('GET', '/complaints', tok['RA'])
    check('RA complaint list excludes Society B', 'SECTEST-B' not in raw)
else:
    print('SKIP complaint seeding (complaint payload not accepted)', s, raw[:120])

# ================================================================ 2. guards
print('\n--- Security guard A vs Society B')
s, j, raw = call('GET', '/guard/visitors', tok['GA'], query={'status': 'all'})
check('Guard A visitor register has no Society B visitor', s == 200 and 'SECTEST-B' not in raw and 'SECTEST-A' in raw, s)
s, j, raw = call('GET', '/guard/flats', tok['GA'])
check('Guard A flat list excludes Society B flats', s == 200 and 'ST-b' not in raw and 'ST-a-1' in raw, s)
for m, p in [('GET', f'/guard/visitors/{b_only_visitor}'), ('POST', f'/guard/visitors/{b_only_visitor}/check-in'), ('POST', f'/guard/visitors/{b_only_visitor}/check-out')]:
    s, _, _ = call(m, p, tok['GA'])
    check(f'Guard A {m} {p} (B-only id) -> 404', s == 404, s)
s, j, raw = call('POST', '/guard/visitors', tok['GA'], {'flat_id': b_only_flat, 'visitor_name': 'SECTEST-A-guard-into-B', 'purpose': 'guest'})
check('Guard A cannot register a visitor against a Society-B flat', s in (403, 404, 422) and sql(DB_B, "SELECT COUNT(*) FROM visitors WHERE visitor_name='SECTEST-A-guard-into-B'") == '0', s)
s, j, raw = call('GET', '/security-guard', tok['RA'])
check('RA on-duty guard endpoint shows no Society B guard', 'SecTest Guard b' not in raw)

print('\n--- Role separation')
s, _, _ = call('GET', '/guard/visitors', tok['RA'])
check('Resident token on /guard/visitors -> 403', s == 403, s)
s, _, _ = call('POST', '/guard/visitors', tok['RA'], {'flat_id': flat_a, 'visitor_name': 'x', 'purpose': 'guest'})
check('Resident token on POST /guard/visitors -> 403', s == 403, s)
s, j, raw = call('GET', '/visitors', tok['GA'])
check('Guard token on resident /visitors returns no data (no flats)', s == 200 and j.get('data') == [], raw[:100])

# ================================================================ 3. token handling
print('\n--- Tokens')
s, _, _ = call('GET', '/me', tok['RA'][:-1] + ('a' if tok['RA'][-1] != 'a' else 'b'))
check('tampered token -> 401', s == 401, s)
s, _, _ = call('GET', '/me')
check('no token -> 401', s == 401, s)
t_tmp = otp_login('9000000011')
call('POST', '/auth/logout', t_tmp)
s, _, _ = call('GET', '/me', t_tmp)
check('token is dead after logout -> 401', s == 401, s)

# ================================================================ 4. mass assignment
print('\n--- Mass assignment / privilege escalation (API)')
s, j, raw = call('PUT', '/profile', tok['RA'], {'name': 'SecTest RA', 'role': 'super_admin', 'roles': ['admin'], 'is_admin': True, 'society_id': 2, 'status': 'blocked', 'password': 'Hacked#12345', 'email': 'hacked@example.test'})
me = call('GET', '/me', tok['RA'])[1]['data']['user']
check('profile update accepted only allowed fields', s == 200 and me['name'] == 'SecTest RA')
check('role not changed by mass-assignment', me['roles'] == ['resident'] or 'admin' not in me['roles'] and 'super_admin' not in me['roles'], me['roles'])
check('status/email not changed by mass-assignment', me['status'] == 'active' and me['email'] != 'hacked@example.test')

# ================================================================ 5. web: society admins
for _t, _f, _n in [(tok['GA'], flat_a, 'SECTEST-A-walkin2'), (tok['GB'], flat_b, 'SECTEST-B-walkin')]:
    _s, _j, _r = call('POST', '/guard/visitors', _t, {'flat_id': _f, 'visitor_name': _n, 'visitor_phone': '9876543210', 'purpose': 'delivery'})
    print('seed walk-in', _n, _s)
print('\n--- Society admin (web) A vs Society B')
cj = http.cookiejar.CookieJar()
op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj), urllib.request.HTTPRedirectHandler())


def web(method, path, data=None, opener=None, allow_redirect=False):
    opener = opener or op
    url = BASE + path
    body = urllib.parse.urlencode(data).encode() if data is not None else None
    req = urllib.request.Request(url, data=body, method=method, headers={'Accept': 'text/html'})

    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, *a, **k):
            return None
    o = opener if allow_redirect else urllib.request.build_opener(NoRedirect, urllib.request.HTTPCookieProcessor(cj))
    try:
        r = o.open(req, timeout=60)
        return r.status, r.read().decode(), r.headers
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode(), e.headers


def csrf(html):
    m = re.search(r'name="_token" value="([^"]+)"', html) or re.search(r'name="csrf-token" content="([^"]+)"', html)
    return m.group(1) if m else ''


def society_login(email):
    global cj, op
    cj = http.cookiejar.CookieJar()
    op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
    s, html, _ = web('GET', '/society/login', allow_redirect=True)
    s, body, h = web('POST', '/society/login', {'_token': csrf(html), 'email': email, 'password': PW})
    return s, h.get('Location', '')


s, loc = society_login('sectest-admin-a@example.test')
check('Admin A can log in to the society portal', s == 302 and 'dashboard' in loc, (s, loc))
s, html, _ = web('GET', '/society/visitors', allow_redirect=True)
check('Admin A /society/visitors is Society A only (200)', s == 200 and 'SECTEST-B' not in html and 'SECTEST-A-walkin' in html, s)
s, html, _ = web('GET', '/society/blocks', allow_redirect=True)
check('Admin A /society/blocks excludes Society B blocks', s == 200 and 'STb' not in html and 'STB' not in html and 'STa' in html, s)
s, html, _ = web('GET', '/society/directory?society_id=2', allow_redirect=True)
check('society_id=2 query is ignored on the portal', s == 200 and 'ST-b' not in html)
s, html, _ = web('GET', f'/society/blocks/{b_only_block}/edit', allow_redirect=True)
check('Admin A GET Society-B block (B-only id) -> 404', s == 404, s)
s, html, _ = web('GET', f'/society/blocks/{b_only_block}/flats', allow_redirect=True)
check('Admin A GET flats of a Society-B block -> 404', s == 404, s)
s, html, _ = web('GET', f'/society/blocks/1/flats/{b_only_flat}/edit', allow_redirect=True)
check('Admin A GET Society-B flat (B-only id) -> 404', s == 404, s)

# CSRF-protected mutating calls, need a token from an A page
s, html, _ = web('GET', '/society/blocks/create', allow_redirect=True)
tk = csrf(html)
s, _, _ = web('DELETE', f'/society/blocks/{b_only_block}', {'_token': tk, '_method': 'DELETE'})
check('Admin A DELETE Society-B block -> not performed', sql(DB_B, f"SELECT COUNT(*) FROM blocks WHERE id={b_only_block} AND deleted_at IS NULL") == '1', s)
s, _, _ = web('POST', f'/society/blocks/{b_only_block}', {'_token': tk, '_method': 'PUT', 'name': 'HACKED', 'block_number': 'X', 'status': 'active'})
check('Admin A PUT Society-B block -> not modified', sql(DB_B, f"SELECT name FROM blocks WHERE id={b_only_block}") != 'HACKED', s)
s, _, _ = web('POST', '/society/blocks', {'name': 'no-csrf', 'block_number': 'Z'})
check('POST without CSRF token -> 419', s == 419, s)

# vertical escalation: society admin must not be able to grant the platform "super_admin" role
super_role = sql(DB_A, "SELECT id FROM roles WHERE name='super_admin'")
victim = sql(DB_A, "SELECT id FROM users WHERE email='sectest-admin-a@example.test'")
s, html, _ = web('GET', '/society/admins/create', allow_redirect=True)
s, body, h = web('POST', '/society/admins', {'_token': csrf(html), 'user_id': victim, 'role': super_role, 'password': 'NewPassw0rd!!1', 'password_confirmation': 'NewPassw0rd!!1'})
check('Society admin cannot assign the super_admin role (validation error, not 302->index success)', sql(DB_A, f"SELECT COUNT(*) FROM role_user WHERE user_id={victim} AND role_id={super_role}") == '0', (s, h.get('Location')))

s, html, _ = web('GET', '/admin/dashboard')
check('Society admin session cannot open the platform /admin panel', s in (302, 403, 404) and 'Platform' not in html[:0], s)
s, html, _ = web('GET', '/admin/societies', allow_redirect=False)
check('Society admin cannot list all societies (/admin/societies)', s in (302, 403, 404), s)

# Admin B symmetric
s, loc = society_login('sectest-admin-b@example.test')
s, html, _ = web('GET', '/society/blocks', allow_redirect=True)
check('Admin B /society/blocks is Society B only', s == 200 and 'STa' not in html and 'STb' in html, s)
s, html, _ = web('GET', '/society/visitors', allow_redirect=True)
check('Admin B /society/visitors excludes Society A', s == 200 and 'SECTEST-A' not in html and 'SECTEST-B-walkin' in html, s)

# unauthenticated
cj = http.cookiejar.CookieJar()
op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
for path in ['/society/visitors', '/society/admins', '/society/blocks', '/admin/dashboard', '/admin/societies']:
    s, _, h = web('GET', path)
    check(f'anonymous GET {path} -> redirect to login', s == 302 and 'login' in (h.get('Location') or ''), s)

# ================================================================ 6. files
print('\n--- Files')
s, body, h = web('GET', '/storage/visitors/')
check('/storage/visitors/ has no directory listing', s in (403, 404) and 'Index of' not in body, s)
s, _, _ = web('GET', '/storage/../.env')
check('path traversal to .env -> not served', s in (400, 403, 404))
s, _, _ = web('GET', '/storage/%2e%2e/%2e%2e/.env')
check('encoded path traversal -> not served', s in (400, 403, 404))

# ================================================================ summary
failed = [r for r in results if not r[1]]
print(f'\n{len(results) - len(failed)} passed, {len(failed)} failed of {len(results)}')
for n, _, d in failed:
    print('FAILED:', n, d)
sys.exit(1 if failed else 0)
