"""Start local provider simulators without loading a cloud profile or live API keys."""
import os,subprocess,secrets
from pathlib import Path
root=Path(__file__).resolve().parents[1]
p=root/'.env.staging';env=dict(l.split('=',1) for l in p.read_text().splitlines())
defaults={'AWS_ACCESS_KEY_ID':secrets.token_hex(12),'AWS_SECRET_ACCESS_KEY':secrets.token_hex(24),'AWS_S3_BUCKET':'client-qa','AWS_REGION':'us-east-1','AWS_S3_ENDPOINT':'http://127.0.0.1:59000','STRIPE_SECRET_KEY':'sk_test_'+secrets.token_hex(12),'STRIPE_API_BASE_URL':'http://127.0.0.1:12111','STRIPE_WEBHOOK_SECRET':secrets.token_hex(24),'NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY':'pk_test_'+secrets.token_hex(12)}
for k,v in defaults.items():env.setdefault(k,v)
p.write_text('\n'.join(k+'='+v for k,v in env.items())+'\n');p.chmod(0o600)
r=subprocess.run(['docker','inspect','clients-qa-stripe'],capture_output=True)
args=['docker','start','clients-qa-stripe'] if r.returncode==0 else ['docker','run','-d','--name','clients-qa-stripe','-p','127.0.0.1:12111:12111','stripe/stripe-mock@sha256:aeb96b83e87a893d2ac005e363ac01ffa6e59a5ad85b49c155dfc9129de02e78']
r=subprocess.run(args,capture_output=True)
if r.returncode:raise SystemExit('Unable to start the Stripe simulator')
print('Local simulator configuration ready. Run moto_server on 59000 and the Inngest dev server on 8288.')
