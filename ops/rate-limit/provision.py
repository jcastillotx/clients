"""Provision on OpenShip; never changes or restarts the active portal."""
import json
import os
from pathlib import Path
import secrets
import shutil
import subprocess

root = Path('/opt/clients-rate-limit')
if os.geteuid() != 0:
    raise SystemExit('Root required')
for path in ['/opt', '/var/lib/docker']:
    if subprocess.check_output(['findmnt', '-rn', '-o', 'UUID', '--target', path], text=True).strip() != '70b0504e-aa41-42c4-a956-400d19cede3e':
        raise SystemExit('Attached site-data drive required')
root.mkdir(mode=0o700, exist_ok=True)
os.chmod(root, 0o700)
source = Path(__file__).resolve().parent
for name in ['compose.yml', 'server.py']:
    if source / name != root / name:
        shutil.copy2(source / name, root / name)
os.chmod(root / 'server.py', 0o644)
env = root / '.env'
if not env.exists():
    env.write_text('RATE_LIMIT_TOKEN=' + secrets.token_hex(32) + '\n')
os.chmod(env, 0o600)
token = dict(line.split('=', 1) for line in env.read_text().splitlines())['RATE_LIMIT_TOKEN']
(root / 'data').mkdir(exist_ok=True)
os.chown(root / 'data', 65532, 65532)
os.chmod(root / 'data', 0o700)
result = subprocess.run(['docker', 'compose', '--env-file', str(env), '-f', str(root / 'compose.yml'), 'up', '-d', '--wait', '--wait-timeout', '120'], capture_output=True, text=True)
(root / 'provision.log').write_text(result.stdout + result.stderr)
os.chmod(root / 'provision.log', 0o600)
if result.returncode:
    raise SystemExit('Shared limiter failed readiness; inspect protected log')
override = Path('/opt/clients-openship/security-env.json')
override.write_text(json.dumps({'SHARED_RATE_LIMIT_URL': 'http://clients-rate-limit:8080', 'SHARED_RATE_LIMIT_TOKEN': token, 'SHARED_RATE_LIMIT_REQUIRED': '1'}))
os.chmod(override, 0o600)
print(json.dumps({'service': 'ready', 'publicPorts': [], 'portalChanged': False}))
