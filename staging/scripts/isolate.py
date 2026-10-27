"""Isolate only the dedicated CLI project, rotating its legacy keys and bind addresses."""
import base64,copy,hashlib,hmac,http.client,json,os,re,secrets,socket,subprocess,time
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
PROJECT='clients-isolated-qa'
SOCKET=str(Path.home()/'.orbstack/run/docker.sock')
class Connection(http.client.HTTPConnection):
 def connect(self):
  self.sock=socket.socket(socket.AF_UNIX,socket.SOCK_STREAM);self.sock.connect(SOCKET)
def api(method,path,body=None):
 c=Connection('localhost');c.request(method,'/v1.47'+path,body=json.dumps(body) if body is not None else None,headers={'Content-Type':'application/json'})
 r=c.getresponse();data=r.read();c.close()
 if r.status not in (200,201,204,304):raise RuntimeError('Docker '+method+' failed '+str(r.status)+': '+data.decode()[:100])
 return json.loads(data) if data else None
def b64(s):return base64.urlsafe_b64encode(s).decode().rstrip('=')
def jwt(role,secret):
 head=b64(b'{"alg":"HS256","typ":"JWT"}')
 body=b64(json.dumps({'iss':'supabase','role':role,'iat':int(time.time()),'exp':int(time.time())+31536000}).encode())
 s=head+'.'+body;return s+'.'+b64(hmac.new(secret.encode(),s.encode(),hashlib.sha256).digest())
containers=json.loads(subprocess.check_output(['docker','ps','-a','--format','{{json .Names}}'],text=True).replace('\n',',' ).rstrip(',').join(['[',']']))
names=[n for n in containers if n.endswith('_'+PROJECT)]
assert names and all(n.startswith('supabase_') for n in names)
configs={n:api('GET','/containers/'+n+'/json') for n in names}
auth=configs['supabase_auth_'+PROJECT]
oldsecret=next(v.split('=',1)[1] for v in auth['Config']['Env'] if v.startswith('GOTRUE_JWT_SECRET='))
secret=secrets.token_hex(32);anon=jwt('anon',secret);service=jwt('service_role',secret)
dbpassword=secrets.token_hex(24)
roles=['postgres','authenticator','supabase_auth_admin','supabase_storage_admin','supabase_admin']
current_dbpassword=next(v.split('=',1)[1] for v in configs['supabase_db_'+PROJECT]['Config']['Env'] if v.startswith('POSTGRES_PASSWORD='))
subprocess.run(['docker','exec','-i','-e','PGPASSWORD='+current_dbpassword,'supabase_db_'+PROJECT,'psql','-U','supabase_admin','-d','postgres','-v','ON_ERROR_STOP=1'],input='\n'.join("ALTER ROLE \""+r+"\" PASSWORD '"+dbpassword+"';" for r in roles),text=True,check=True,capture_output=True)
oldanon=jwt('anon',oldsecret) # actual gateway tokens are extracted below
replacements={oldsecret:secret}
storage=configs['supabase_storage_'+PROJECT]
for entry in storage['Config']['Env']:
 k,_,v=entry.partition('=')
 if k=='ANON_KEY':replacements[v]=anon
 if k=='SERVICE_KEY':replacements[v]=service
jwks=json.dumps({'keys':[{'kty':'oct','k':b64(secret.encode()),'alg':'HS256'}]})
def replace(value):
 if isinstance(value,str):
  for a,b in replacements.items():value=value.replace(a,b)
  for token in set(re.findall(r'eyJ[A-Za-z0-9_.-]+',value)):
   try:role=json.loads(base64.urlsafe_b64decode(token.split('.')[1]+'===' )).get('role')
   except Exception:continue
   if role=='anon':value=value.replace(token,anon)
   if role=='service_role':value=value.replace(token,service)
  value=re.sub(r':postgres@', ':'+dbpassword+'@',value)
  return value
 if isinstance(value,list):return [replace(v) for v in value]
 if isinstance(value,dict):return {k:replace(v) for k,v in value.items()}
 return value
for name in names:
 old=configs[name];config=replace(copy.deepcopy(old['Config']))
 config.pop('Hostname',None);config.pop('Domainname',None)
 env=dict(e.split('=',1) for e in config['Env'])
 env['GOTRUE_JWT_KEYS']='[]'
 if name.startswith('supabase_auth_'):env.update({'GOTRUE_EXTERNAL_EMAIL_ENABLED':'true','GOTRUE_MFA_TOTP_ENROLL_ENABLED':'true','GOTRUE_MFA_TOTP_VERIFY_ENABLED':'true'})
 for k in ['PGRST_JWT_SECRET','AUTH_JWT_SECRET']: 
  if k in env:env[k]=secret
 if 'JWT_JWKS' in env:env['JWT_JWKS']=jwks
 for k in ['GOTRUE_JWT_VALID_METHODS','GOTRUE_JWT_VALIDMETHODS']:
  if k in env:env[k]='HS256'
 for k in env:
  if (k.endswith('PASSWORD') or k=='PGPASSWORD') and env[k]=='postgres':env[k]=dbpassword
 config['Env']=[k+'='+v for k,v in env.items()]
 host=copy.deepcopy(old['HostConfig'])
 for bindings in (host.get('PortBindings') or {}).values():
  for binding in bindings:binding['HostIp']='127.0.0.1'
 if name.startswith('supabase_inbucket_'):host.setdefault('PortBindings',{})['1025/tcp']=[{'HostIp':'127.0.0.1','HostPort':'55325'}]
 config['HostConfig']=host
 config['NetworkingConfig']={'EndpointsConfig':{net:{'Aliases':v.get('Aliases') or [name]} for net,v in old['NetworkSettings']['Networks'].items()}}
 # CLI-generated files are in the container writable layer. Preserve them in a private image.
 committed=api('POST','/commit?container='+old['Id'])
 config['Image']=committed['Id']
 api('POST','/containers/'+name+'/stop?t=10');api('DELETE','/containers/'+name)
 new=api('POST','/containers/create?name='+name,config);api('POST','/containers/'+new['Id']+'/start')
# Keep credentials private and separate from every production/development stack.
path=ROOT/'.env.staging'
values={'NEXT_PUBLIC_APP_URL':'http://127.0.0.1:3100','NEXT_PUBLIC_SITE_URL':'http://127.0.0.1:3100','NEXT_PUBLIC_SUPABASE_URL':'http://127.0.0.1:55321','NEXT_PUBLIC_SUPABASE_ANON_KEY':anon,'SUPABASE_SERVICE_ROLE_KEY':service,'DATABASE_URL':'postgresql://postgres:'+dbpassword+'@127.0.0.1:55322/postgres','ENCRYPTION_KEY':secrets.token_hex(32),'INNGEST_DEV':'1','INNGEST_BASE_URL':'http://127.0.0.1:8288','NEXT_TELEMETRY_DISABLED':'1'}
path.write_text('\n'.join(k+'='+v for k,v in values.items())+'\n');path.chmod(0o600)
print('Dedicated staging containers use rotated auth keys and loopback-only port bindings.')
