import os,subprocess
from pathlib import Path
root=Path(__file__).resolve().parents[2]
values=dict(l.split('=',1) for l in (root/'staging/.env.staging').read_text().splitlines() if l and not l.startswith('#'))
values['STAGING_ISOLATED']='1'
values['NEXT_TELEMETRY_DISABLED']='1'
env={k:v for k,v in os.environ.items() if k in ['HOME','PATH','LANG','TMPDIR','USER','SHELL']}
env.update(values)
raise SystemExit(subprocess.call(['node',str(root/'node_modules/next/dist/bin/next'),'dev','--hostname','127.0.0.1','--port','3100'],cwd=root,env=env))
