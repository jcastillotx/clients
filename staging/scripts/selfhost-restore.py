import hashlib,json,secrets,subprocess,time
from pathlib import Path
root=Path(__file__).resolve().parents[2]
backup=root/'staging/artifacts/selfhost-backup'
manifest=json.loads((backup/'manifest.json').read_text())
if manifest['project']!='clients-selfhost-local' or not manifest['quiesced']:raise SystemExit('Quiesced synthetic backup required')
for name,digest in manifest['sha256'].items():
 if name not in ['postgres.dump','redis.rdb'] or hashlib.sha256((backup/name).read_bytes()).hexdigest()!=digest:raise SystemExit('Invalid backup checksum')
names=['qa-clients-jobs-restore-pg','qa-clients-jobs-restore-redis']
def run(args,**kw):
 r=subprocess.run(args,capture_output=True,**kw)
 if r.returncode:
  diagnostic=r.stderr.decode(errors='replace') if isinstance(r.stderr,bytes) else r.stderr
  raise RuntimeError(diagnostic[:1600])
 return r
try:
 run(['docker','run','-d','--name',names[0],'--network','none','-e','POSTGRES_USER=inngest','-e','POSTGRES_DB=inngest','-e','POSTGRES_PASSWORD='+secrets.token_hex(32),'postgres:17-alpine'])
 for _ in range(60):
  r=subprocess.run(['docker','exec',names[0],'pg_isready','-U','inngest','-d','inngest'],capture_output=True)
  logs=subprocess.check_output(['docker','logs',names[0]],stderr=subprocess.DEVNULL,text=True)
  if r.returncode==0 and 'init process complete' in logs:break
  time.sleep(.25)
 with (backup/'postgres.dump').open('rb') as source:run(['docker','exec','-i',names[0],'pg_restore','-U','inngest','-d','inngest','--clean','--if-exists'],stdin=source)
 counts=run(['docker','exec',names[0],'psql','-U','inngest','-d','inngest','-t','-A','-c',"SELECT json_build_object('apps',(SELECT count(*) FROM apps),'functions',(SELECT count(*) FROM functions),'runs',(SELECT count(*) FROM function_runs));"],text=True)
 data=json.loads(counts.stdout)
 if data['apps']<1 or data['functions']!=7 or data['runs']<1:raise SystemExit('Restored registry or history missing')
 run(['docker','create','--name',names[1],'--network','none','redis:7-alpine','redis-server','--save','','--appendonly','no'])
 run(['docker','cp',str(backup/'redis.rdb'),names[1]+':/data/dump.rdb'])
 run(['docker','start',names[1]])
 for _ in range(60):
  r=subprocess.run(['docker','exec',names[1],'redis-cli','DBSIZE'],capture_output=True,text=True)
  if r.returncode==0:break
  time.sleep(.25)
 if r.returncode or int(r.stdout.strip())<1:raise SystemExit('Restored Redis state missing')
 result={'postgresRestored':True,'redisRestored':True,'restoredRegistry':data,'restoredRedisKeys':int(r.stdout.strip()),'isolatedNetwork':True,'temporaryRestoreContainersRemoved':True}
 (root/'staging/artifacts/selfhost-restore.json').write_text(json.dumps(result,indent=2)+'\n')
 print(json.dumps(result))
finally:
 for name in names:subprocess.run(['docker','rm','--force','--volumes',name],capture_output=True)
