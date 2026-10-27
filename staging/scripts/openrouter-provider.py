"""Run the separate real-OpenRouter acceptance app with synthetic local data only."""
import json,os,subprocess,sys
from pathlib import Path
root=Path(__file__).resolve().parents[2]
config=json.loads(Path(sys.argv[1]).read_text())
values=dict(l.split('=',1) for l in (root/'staging/.env.staging').read_text().splitlines() if l and not l.startswith('#'))
values.update(STAGING_ISOLATED='0',STAGING_PROVIDER_ACCEPTANCE='openrouter',NEXT_PUBLIC_APP_URL='http://127.0.0.1:3101',NEXT_PUBLIC_SITE_URL='http://127.0.0.1:3101',NEXT_TELEMETRY_DISABLED='1',MARKETING_AGENT_PROVIDER='openrouter',OPENROUTER_API_KEY=config['apiKey'])
values.pop('MARKETING_AI_SIMULATOR_URL',None)
for key in ['OPENAI_API_KEY','ANTHROPIC_API_KEY','GOOGLE_AI_API_KEY','RESEND_API_KEY','MICROSOFT_EMAIL_CLIENT_SECRET']:
 if values.get(key):raise SystemExit('Unexpected external credential in local fixtures')
env={k:v for k,v in os.environ.items() if k in ['HOME','PATH','LANG','TMPDIR','USER','SHELL']}
env.update(values)
raise SystemExit(subprocess.call(['node',str(root/'node_modules/next/dist/bin/next'),'dev','--hostname','127.0.0.1','--port','3101'],cwd=root,env=env))
