"""Provision once; subsequent runs preserve this staging project's own credentials."""
from pathlib import Path
import subprocess,os
root=Path(__file__).resolve().parents[2]
if not (root/'staging/.env.staging').exists():
 subprocess.run(['supabase','start','--workdir',str(root/'staging')],check=True)
 subprocess.run(['python3',str(root/'staging/scripts/isolate.py')],check=True)
else:
 import json
 rows=subprocess.check_output(['docker','ps','-a','--format','{{.Names}}'],text=True).splitlines()
 for name in rows:
  if name.endswith('_clients-isolated-qa'):subprocess.run(['docker','start',name],check=True,stdout=subprocess.DEVNULL)
subprocess.run(['python3',str(root/'staging/scripts/services.py')],check=True)
print('Dedicated staging services ready; production and other local Supabase projects were not changed.')
