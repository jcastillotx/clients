#!/usr/bin/env python3
"""Publish the approved catalog without changing prices, notes or tenant records.
Default is dry-run. --apply only adds missing package IDs, preserving later edits.
Production credentials stay inside the existing application container.
"""
import argparse, json, pathlib, shlex, subprocess, time
p = argparse.ArgumentParser(description=__doc__)
p.add_argument("--apply", action="store_true")
a = p.parse_args()
packages = json.loads(pathlib.Path(__file__).with_name("packages.json").read_text())
category = {"WordPress Design":"Websites","E-Commerce":"Commerce","SEO Services":"Search","Online Advertising":"Advertising","Creative Design":"Creative","Branding":"Branding","Digital Strategy":"Strategy","Website Audit":"Assessment","Custom Web Design":"Development"}
for package in packages:
    package["category"] = category[package.pop("service")]
    package["published"] = True
js = r'''const packages=PACKAGES,apply=APPLY;
const base=process.env.NEXT_PUBLIC_SUPABASE_URL;
const key=process.env.SUPABASE_SERVICE_ROLE_KEY||process.env.SUPABASE_SERVICE_KEY;
const headers={apikey:key,Authorization:'Bearer '+key,'Content-Type':'application/json'};
async function call(path,options={}){const r=await fetch(new URL(path,base),{...options,headers,redirect:'error'});if(!r.ok)throw Error('Catalog operation failed: '+r.status);const t=await r.text();return t?JSON.parse(t):null;}
const rows=await call('/rest/v1/service_templates?is_active=eq.true&select=id,name,category,metadata');
const changes=[];
for(const row of rows){
 const additions=packages.filter(p=>p.category===row.category).map(({category,...p})=>p);
 if(!additions.length)continue;
 const metadata=row.metadata||{},existing=metadata.publicPackages||[];
 if(!Array.isArray(existing))throw Error('Unexpected catalog metadata');
 const missing=additions.filter(p=>!existing.some(e=>e.id===p.id));
 if(!missing.length)continue;
 if(rows.filter(r=>r.category===row.category).length!==1)throw Error('Ambiguous service category');
 if(apply)await call('/rest/v1/service_templates?id=eq.'+row.id,{method:'PATCH',body:JSON.stringify({metadata:{...metadata,publicPackages:[...existing,...missing]}})});
 changes.push({service:row.name,added:missing.map(p=>p.id)});
}
for(const p of packages)if(!rows.some(r=>r.category===p.category))throw Error('Missing service category');
console.log(JSON.stringify({applied:apply,changes}));
'''.replace("PACKAGES", json.dumps(packages)).replace("APPLY", str(a.apply).lower())
command = "docker exec openship-kre8ivdesigns-clients-clients-designs-candidate node --input-type=module -e " + shlex.quote(js)
def aws(*args):
    return json.loads(subprocess.check_output(["aws",*args,"--profile","kre8iv-whm","--region","us-west-2","--output","json"],text=True))
assert aws("sts","get-caller-identity")["Account"] == "556348027172", "Wrong AWS account"
sent=aws("ssm","send-command","--document-name","AWS-RunShellScript","--instance-ids","i-03631eb1405b01b8e","--parameters",json.dumps({"commands":[command]}))
id=sent["Command"]["CommandId"]; print("Catalog command:",id,flush=True)
for _ in range(40):
    time.sleep(2)
    result=aws("ssm","get-command-invocation","--command-id",id,"--instance-id","i-03631eb1405b01b8e")
    if result["Status"] in ["Pending","InProgress","Delayed"]:continue
    if result["Status"]!="Success":raise SystemExit("Catalog operation failed; inspect "+id)
    print(result.get("StandardOutputContent",""));break
else:raise SystemExit("Timed out; inspect command before retrying")
