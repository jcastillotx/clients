"""Recreate one owned staging service without changing its mounts or network aliases."""
import copy,http.client,json,socket,subprocess
from pathlib import Path
class Connection(http.client.HTTPConnection):
 def connect(self):
  self.sock=socket.socket(socket.AF_UNIX,socket.SOCK_STREAM);self.sock.connect(str(Path.home()/'.orbstack/run/docker.sock'))
def api(method,path,body=None):
 c=Connection('localhost');c.request(method,'/v1.47'+path,json.dumps(body) if body is not None else None,{'Content-Type':'application/json'})
 r=c.getresponse();data=r.read();c.close()
 if r.status not in (200,201,204,304):raise RuntimeError('Docker request failed '+str(r.status))
 return json.loads(data) if data else None
def change(name,updates):
 assert name.endswith('_clients-isolated-qa')
 old=api('GET','/containers/'+name+'/json');cfg=copy.deepcopy(old['Config']);cfg.pop('Hostname',None);cfg.pop('Domainname',None)
 env=dict(x.split('=',1) for x in cfg['Env']);env.update(updates);cfg['Env']=[k+'='+v for k,v in env.items()]
 cfg['HostConfig']=old['HostConfig'];cfg['NetworkingConfig']={'EndpointsConfig':{n:{'Aliases':e.get('Aliases') or [name]} for n,e in old['NetworkSettings']['Networks'].items()}}
 api('POST','/containers/'+name+'/stop?t=10');api('DELETE','/containers/'+name)
 r=api('POST','/containers/create?name='+name,cfg);api('POST','/containers/'+r['Id']+'/start')
