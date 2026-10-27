import fs from 'node:fs';
import { createRequire } from 'node:module';
const require=createRequire(new URL('../../package.json',import.meta.url));
const {createServerClient}=require('@supabase/ssr'),postgres=require('postgres');
const root=new URL('../',import.meta.url);
const env=Object.fromEntries(fs.readFileSync(new URL('.env.staging',root),'utf8').trim().split('\n').map(l=>{const i=l.indexOf('=');return[l.slice(0,i),l.slice(i+1)]}));
if(!/^http:\/\/(127\.0\.0\.1|localhost):3100$/.test(env.NEXT_PUBLIC_APP_URL))throw Error('Local isolated app required');
for(const [key,port] of [['DATABASE_URL','55322'],['NEXT_PUBLIC_SUPABASE_URL','55321']]){const u=new URL(env[key]);if(!['127.0.0.1','localhost'].includes(u.hostname)||u.port!==port)throw Error('Dedicated local staging service required: '+key)}
const fixtures=JSON.parse(fs.readFileSync(new URL('.accounts.json',root),'utf8'));
const sql=postgres(env.DATABASE_URL,{max:1}),results=[];
for(const name of ['039_marketing_skill_tools.sql','040_ai_task_json_objects.sql'])await sql.unsafe(fs.readFileSync(new URL('../../lib/db/migrations/'+name,import.meta.url),'utf8'));
async function check(name,fn){try{await fn();results.push({name,passed:true});console.log('PASS',name)}catch(e){results.push({name,passed:false,error:e.message});console.log('FAIL',name,e.message)}fs.writeFileSync(new URL('artifacts/marketing-tools.json',root),JSON.stringify(results,null,2))}
function expect(v,m){if(!v)throw Error(m)}
async function session(name){let jar=[];const c=createServerClient(env.NEXT_PUBLIC_SUPABASE_URL,env.NEXT_PUBLIC_SUPABASE_ANON_KEY,{cookies:{getAll:()=>jar,setAll:all=>{for(const x of all){jar=jar.filter(y=>y.name!==x.name);jar.push(x)}}}});const a=fixtures.accounts[name];const {error}=await c.auth.signInWithPassword({email:a.email,password:a.password});if(error)throw error;return()=>jar.map(x=>x.name+'='+x.value).join('; ')}
const a=await session('alpha'),b=await session('beta');
async function call(cookie,path,body){const r=await fetch(env.NEXT_PUBLIC_APP_URL+path,{method:body?'POST':'GET',headers:{cookie:cookie?.()||'','content-type':'application/json'},body:body?JSON.stringify(body):undefined});return{status:r.status,json:await r.json()}}
let tools=[],simulatorReady=false;const created=[];
await check('Anonymous marketing catalog denied',async()=>expect((await call(null,'/api/marketing/tools')).status===401,'anonymous access'));
await check('Client gets all 48 marketing tools and simulator label',async()=>{const x=await call(a,'/api/marketing/tools');expect(x.status===200,'catalog failed');expect(x.json.data.tools.length===48&&x.json.data.simulated&&x.json.data.providerReady,'library incomplete');tools=x.json.data.tools;simulatorReady=true});
if(!simulatorReady)throw Error('Refusing workflow writes without the isolated AI simulator');
await check('Client cannot select another tenant catalog',async()=>expect((await call(a,'/api/marketing/tools?clientId='+fixtures.clients.beta)).status===403,'foreign catalog visible'));
await check('Client cannot forge another tenant run',async()=>expect((await call(a,'/api/marketing/tools',{clientId:fixtures.clients.beta,skillId:'google-ads',brief:'Analyze this synthetic business safely.'})).status===403,'foreign run accepted'));
await check('Unknown tool and invalid brief rejected',async()=>expect((await call(a,'/api/marketing/tools',{skillId:'invented-tool',brief:'x'})).status===400,'invalid request accepted'));
for(const tool of tools){await check('Client draft: '+tool.id,async()=>{const guide=await call(a,'/api/marketing/tools?skillId='+tool.id);expect(guide.status===200&&guide.json.data.methodology.length>100,'methodology absent');const x=await call(a,'/api/marketing/tools',{skillId:tool.id,brief:'Prepare an evidence-aware marketing draft for a synthetic design studio. No external actions.',evidence:'Synthetic test data only. No real metrics provided.'});expect(x.status===201,'run failed: '+JSON.stringify(x.json.error));expect(x.json.data.simulated&&x.json.data.artifact.analysis.includes('SIMULATED'),'unlabeled fixture');const [row]=await sql`select client_id,status,output,metadata from ai_tasks where id=${x.json.data.id}`;expect(row.client_id===fixtures.clients.alpha&&row.status==='completed'&&row.metadata.approvalStatus==='pending_approval','wrong persistence');created.push(x.json.data.id)})}
await check('Other client history excludes these drafts',async()=>{const x=await call(b,'/api/marketing/tools');expect(x.status===200&&!x.json.data.history.some(r=>created.includes(r.id)),'foreign results exposed')});
await check('Client can reload its saved drafts',async()=>{const x=await call(a,'/api/marketing/tools');expect(x.status===200&&x.json.data.history.some(r=>created.includes(r.id)&&r.output?.result?.analysis),'saved drafts missing')});
await check('Provider failure persists failed status',async()=>{const x=await call(a,'/api/marketing/tools',{skillId:'google-ads',brief:'SIMULATE_PROVIDER_ERROR for isolated error handling'});expect(x.status===502,'provider error hidden');const [row]=await sql`select status from ai_tasks where client_id=${fixtures.clients.alpha} order by created_at desc limit 1`;expect(row.status==='failed','failure not persisted')});
await check('Invalid provider artifact rejected',async()=>expect((await call(a,'/api/marketing/tools',{skillId:'google-ads',brief:'SIMULATE_INVALID_ARTIFACT for isolated schema validation'})).status===502,'invalid model artifact accepted'));
await check('Client feature disable blocks catalog and run',async()=>{const [f]=await sql`select id from features where name='marketing_skill_tools'`;await sql`insert into client_features(client_id,feature_id,is_enabled) values(${fixtures.clients.beta},${f.id},false) on conflict(client_id,feature_id) do update set is_enabled=false`;try{expect((await call(b,'/api/marketing/tools')).status===403,'disabled catalog visible');expect((await call(b,'/api/marketing/tools',{skillId:'google-ads',brief:'Prepare an evidence-aware draft for this test account.'})).status===403,'disabled run allowed')}finally{await sql`delete from client_features where client_id=${fixtures.clients.beta} and feature_id=${f.id}`}});
await check('Feature priority and overview agree for user, role and client overrides',async()=>{
 const [f]=await sql`select id from features where name='marketing_skill_tools'`;
 const [role]=await sql`select role_id from user_roles where user_id=${fixtures.accounts.alpha.id} limit 1`;
 try{
  await sql`insert into client_features(client_id,feature_id,is_enabled) values(${fixtures.clients.alpha},${f.id},false) on conflict(client_id,feature_id) do update set is_enabled=false`;
  expect((await call(a,'/api/marketing/tools')).status===403,'client disable ignored');
  await sql`insert into role_features(role_id,feature_id,is_enabled) values(${role.role_id},${f.id},true) on conflict(role_id,feature_id) do update set is_enabled=true`;
  expect((await call(a,'/api/marketing/tools')).status===200,'role override not respected');
  let overview=await call(a,'/api/marketing/features');expect(overview.json.data.features.find(x=>x.name==='marketing_skill_tools')?.isEnabled===true,'overview disagrees with role override');
  await sql`insert into user_features(user_id,feature_id,is_enabled) values(${fixtures.accounts.alpha.id},${f.id},false) on conflict(user_id,feature_id) do update set is_enabled=false`;
  expect((await call(a,'/api/marketing/tools')).status===403,'user disable ignored');
  overview=await call(a,'/api/marketing/features');expect(overview.json.data.features.find(x=>x.name==='marketing_skill_tools')?.isEnabled===false,'overview disagrees with user override');
 }finally{
  await sql`delete from user_features where user_id=${fixtures.accounts.alpha.id} and feature_id=${f.id}`;
  await sql`delete from role_features where role_id=${role.role_id} and feature_id=${f.id}`;
  await sql`delete from client_features where client_id=${fixtures.clients.alpha} and feature_id=${f.id}`;
 }
});
await sql.end();const failed=results.filter(x=>!x.passed);console.log(JSON.stringify({passed:results.length-failed.length,total:results.length,failed:failed.length}));if(failed.length)process.exitCode=1;
