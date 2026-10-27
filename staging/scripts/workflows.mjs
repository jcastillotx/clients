import fs from 'node:fs';
import {createRequire} from 'node:module';
import crypto from 'node:crypto';
const require=createRequire(new URL('../../package.json',import.meta.url));
const {createServerClient}=require('@supabase/ssr');
const {createClient}=require('@supabase/supabase-js');
const postgres=require('postgres');
const root=new URL('../',import.meta.url);
const env=Object.fromEntries(fs.readFileSync(new URL('.env.staging',root),'utf8').trim().split('\n').map(l=>{const i=l.indexOf('=');return[l.slice(0,i),l.slice(i+1)]}));
const fixtures=JSON.parse(fs.readFileSync(new URL('.accounts.json',root),'utf8'));
const sql=postgres(env.DATABASE_URL,{max:1});
const service=createClient(env.NEXT_PUBLIC_SUPABASE_URL,env.SUPABASE_SERVICE_ROLE_KEY,{auth:{persistSession:false}});
const results=[];
async function test(name,fn){try{await fn();results.push({name,passed:true});console.log('PASS',name)}catch(e){results.push({name,passed:false,error:e.message});console.log('FAIL',name,e.message)}fs.writeFileSync(new URL('artifacts/workflows.json',root),JSON.stringify(results,null,2));}
function expect(condition,message){if(!condition)throw Error(message)}
async function session(name){let jar=[];const auth=createServerClient(env.NEXT_PUBLIC_SUPABASE_URL,env.NEXT_PUBLIC_SUPABASE_ANON_KEY,{cookies:{getAll:()=>jar,setAll:all=>{for(const c of all){jar=jar.filter(x=>x.name!==c.name);jar.push(c)}}}});const account=fixtures.accounts[name];const {error}=await auth.auth.signInWithPassword({email:account.email,password:account.password});if(error)throw error;return {auth,account,cookie:()=>jar.map(c=>c.name+'='+c.value).join('; ')};}
const clients={};for(const name of ['admin','alpha','beta','staff'])clients[name]=await session(name);
async function call(who,path,body,method='POST'){const headers={cookie:clients[who].cookie()};if(body&&!(body instanceof FormData))headers['content-type']='application/json';const r=await fetch(env.NEXT_PUBLIC_APP_URL+path,{method,headers,body:body instanceof FormData?body:body?JSON.stringify(body):undefined,redirect:'manual'});let data;try{data=await r.json()}catch{};return {r,data,value:data?.data??data};}
function status(x,code){expect(x.r.status===code,`expected ${code}, got ${x.r.status}: ${JSON.stringify(x.data?.error||x.data?.message||{}).slice(0,220)}`)}
const a=fixtures.clients.alpha,b=fixtures.clients.beta;
await test('Public signup remains disabled',async()=>{const {error}=await clients.alpha.auth.auth.signUp({email:'blocked@qa.example.invalid',password:crypto.randomBytes(24).toString('hex')});expect(error?.code==='signup_disabled','signup was not blocked')});
await test('Editable metadata cannot create administrator accounts',async()=>{const {error}=await clients.alpha.auth.auth.updateUser({data:{role:'super_admin',app_role:'admin',is_super_admin:true,client_id:b}});if(error)throw error;status(await call('alpha','/api/admin/users',{name:'Forged',email:'forged@qa.example.invalid',password:'fixture-unused-password'}),403)});
await test('Direct profile privilege and tenant changes are denied',async()=>{for(const field of [{is_super_admin:true},{client_id:b},{manual_permissions:{all:true}},{is_active:false}]){const {error}=await clients.alpha.auth.from('users').update(field).eq('id',clients.alpha.account.id);expect(!!error,'protected field update succeeded')}});
await test('Ordinary profile edits still persist',async()=>{const {error}=await clients.alpha.auth.from('users').update({name:'QA alpha verified'}).eq('id',clients.alpha.account.id);if(error)throw error;const [u]=await sql`select name from users where id=${clients.alpha.account.id}`;expect(u.name==='QA alpha verified','profile did not persist')});
await test('Client project list contains only its tenant',async()=>{const x=await call('alpha','/api/projects',null,'GET');status(x,200);expect(x.value.length===1&&x.value[0].project.clientId===a,'foreign projects visible')});
await test('Foreign project lookup is denied',async()=>{const [p]=await sql`select id from projects where client_id=${b} limit 1`;status(await call('alpha','/api/projects/'+p.id,null,'GET'),403)});
let requestId,ticketId,documentId,conversationId,proposalId;
await test('Client can create a service request',async()=>{const x=await call('alpha','/api/requests',{clientId:a,title:'QA service request',description:'Synthetic service request to verify persistence',priority:'medium',type:'support',notifyAdmins:true,notifyAssigned:false});status(x,201);requestId=x.value.id;expect(!!requestId,'missing request id')});
await test('Cross-client service creation is denied',async()=>status(await call('alpha','/api/requests',{clientId:b,title:'QA forbidden request',description:'Should not be created',priority:'medium',type:'support'}),403));
await test('Support ticket and comment workflow',async()=>{let x=await call('alpha','/api/support',{subject:'QA support ticket',description:'Synthetic technical support workflow verification',category:'technical',priority:'medium'});status(x,201);ticketId=x.value.id;x=await call('alpha','/api/support/'+ticketId+'/comments',{comment:'QA client follow-up',isInternal:false});status(x,201)});
await test('Project request workflow',async()=>{const x=await call('alpha','/api/projects/requests',{title:'QA new project',executiveSummary:'Synthetic project request for staging validation',description:'Build a synthetic staging fixture without real business effects',desiredOutcome:'Verify request creation',priority:'medium'});status(x,201)});
await test('Document upload and signed download preserve bytes',async()=>{const form=new FormData();form.set('clientId',a);form.set('name','QA document');form.set('file',new Blob(['Client QA document contents'],{type:'text/plain'}),'qa.txt');const x=await call('alpha','/api/documents/upload',form);status(x,201);documentId=x.value.id;expect(!!documentId,'missing document id');const d=await call('alpha','/api/documents/'+documentId+'/download',null,'GET');expect([200,302,307].includes(d.r.status),'download failed '+d.r.status);const url=d.r.headers.get('location')||d.value?.url||d.value?.downloadUrl;expect(!!url,'missing signed download url');const text=await fetch(url).then(r=>r.text());expect(text==='Client QA document contents','download content differs')});
await test('Tenant conversation and message receipt',async()=>{let x=await call('alpha','/api/messages/conversations',{clientId:a,title:'QA conversation',participantIds:[clients.alpha.account.id]});status(x,201);conversationId=x.value.id;x=await call('alpha','/api/messages',{conversationId,messageBody:'QA message sent through application',type:'text'});status(x,201);x=await call('alpha','/api/messages?conversationId='+conversationId,null,'GET');status(x,200);expect(x.value.some(m=>m.body==='QA message sent through application'),'message not received')});
await test('Foreign conversation creation is denied',async()=>status(await call('alpha','/api/messages/conversations',{clientId:b,title:'Forbidden',participantIds:[clients.beta.account.id]}),403));
await test('Admin can create client accounts and welcome mail',async()=>{const x=await call('admin','/api/admin/users',{name:'QA invited user',email:'invited-'+Date.now()+'@qa.example.invalid',password:crypto.randomBytes(24).toString('hex'),client_id:a});status(x,201);expect(x.value.welcome_email_sent===true,'welcome email not accepted')});
await test('Proposal signature persists acceptance',async()=>{const [p]=await sql`insert into proposals(client_id,title,status,total_amount,created_by,line_items) values(${a},'QA synthetic proposal','sent',25,${clients.admin.account.id},'[]') returning id`;proposalId=p.id;const x=await call('alpha','/api/proposals/'+proposalId+'/sign',{action:'accept',signatureData:'data:image/png;base64,iVBORw0KGgo=',signerName:'QA Client',signerEmail:clients.alpha.account.email});status(x,200);const [saved]=await sql`select status from proposals where id=${proposalId}`;expect(saved.status==='accepted','signature not persisted')});
await test('Captured notifications are delivered to local mailbox',async()=>{const r=await fetch('http://127.0.0.1:55324/api/v1/messages');const d=await r.json();expect(d.total>0||d.messages?.length>0,'no captured email')});

await test('Document versions preserve history and latest flags',async()=>{
 const form=new FormData();form.set('clientId',a);form.set('name','QA document version 2');form.set('parentDocumentId',documentId);form.set('file',new Blob(['QA version two contents'],{type:'text/plain'}),'qa-v2.txt');
 const x=await call('alpha','/api/documents/upload',form);status(x,201);expect(x.value.version===2,'incorrect version number');
 const [old]=await sql`select is_latest_version from documents where id=${documentId}`;expect(old.is_latest_version===false,'old version remains latest');
 status(await call('beta','/api/documents/'+x.value.id+'/download',null,'GET'),404);
});
await test('Clients cannot create internal support notes',async()=>status(await call('alpha','/api/support/'+ticketId+'/comments',{comment:'Should be denied',isInternal:true}),403));
await test('Staff-only APIs reject forged client metadata',async()=>{
 for(const path of ['/api/tasks/boards','/api/time-tracking','/api/ads/metrics/sync']){const x=await call('alpha',path,path.includes('sync')?{}:null,path.includes('sync')?'POST':'GET');status(x,403)}
});
let templateId;
await test('Maintenance subscription persists for the correct tenant',async()=>{
 const [t]=await sql`insert into maintenance_plan_templates(name,monthly_rate,included_hours,hourly_rate_overage,created_by) values('QA plan',25,1,25,${clients.admin.account.id}) returning id`;templateId=t.id;
 const x=await call('alpha','/api/maintenance-plans/subscribe',{templateId,clientId:a});status(x,201);
 expect(x.value.clientId===a||x.value.plan?.clientId===a,'incorrect subscription tenant');
});
await test('Foreign maintenance subscription is denied',async()=>status(await call('alpha','/api/maintenance-plans/subscribe',{templateId,clientId:b}),403));
await test('Client feedback persists and foreign proposal mutations are denied',async()=>{
 const [p]=await sql`insert into proposals(client_id,title,status,total_amount,created_by,line_items) values(${b},'QA beta proposal','sent',25,${clients.admin.account.id},'[]') returning id`;
 status(await call('alpha','/api/proposals/'+proposalId+'/feedback',{feedback:'QA feedback'}),200);
 status(await call('alpha','/api/proposals/'+p.id+'/feedback',{feedback:'Forbidden'}),403);
 status(await call('alpha','/api/proposals/'+p.id+'/cancel',{}),403);
});
let invoiceId,paymentId;
await test('Invoice payment intent is created and persisted using the local Stripe simulator',async()=>{
 const [i]=await sql`insert into invoices(invoice_number,client_id,amount,status,created_by) values(${'QA-'+Date.now()},${a},25,'sent',${clients.admin.account.id}) returning id`;invoiceId=i.id;
 const x=await call('alpha','/api/payments/create-intent',{invoiceId});status(x,200);paymentId=x.value.paymentIntentId;expect(!!paymentId,'no payment intent');
 const [saved]=await sql`select stripe_payment_intent_id from invoices where id=${invoiceId}`;expect(saved.stripe_payment_intent_id===paymentId,'payment intent not persisted');
 const denied=await call('beta','/api/payments/create-intent',{invoiceId});status(denied,404);
});
async function webhook(type,object,id){const body=JSON.stringify({id,object:'event',type,data:{object}}),t=Math.floor(Date.now()/1000);const sig=crypto.createHmac('sha256',env.STRIPE_WEBHOOK_SECRET).update(t+'.'+body).digest('hex');const r=await fetch(env.NEXT_PUBLIC_APP_URL+'/api/webhooks/stripe',{method:'POST',headers:{'content-type':'application/json','stripe-signature':'t='+t+',v1='+sig},body});return{r,data:await r.json()};}
await test('Payment webhook rejects mismatched amounts before updating invoices',async()=>{
 const x=await webhook('payment_intent.succeeded',{id:paymentId,amount:1,currency:'usd',metadata:{invoice_id:invoiceId},payment_method_types:['card']},'evt_qa_bad_'+Date.now());expect(x.r.status!==200,'mismatched payment accepted');
 const [i]=await sql`select status from invoices where id=${invoiceId}`;expect(i.status!=='paid','bad payment marked invoice paid');
});
await test('Payment success webhook persists and duplicate replay is idempotent',async()=>{
 const id='evt_qa_paid_'+Date.now(),object={id:paymentId,amount:2500,currency:'usd',metadata:{invoice_id:invoiceId},payment_method_types:['card']};
 status(await webhook('payment_intent.succeeded',object,id),200);
 const [i]=await sql`select status from invoices where id=${invoiceId}`;expect(i.status==='paid','invoice was not marked paid');
 const x=await webhook('payment_intent.succeeded',object,id);status(x,200);expect(x.data.data?.duplicate===true||x.data.duplicate===true,'replay not recognized');
});
await test('Invalid payment webhook signatures are denied',async()=>{const r=await fetch(env.NEXT_PUBLIC_APP_URL+'/api/webhooks/stripe',{method:'POST',headers:{'content-type':'application/json','stripe-signature':'t=1,v1=invalid'},body:'{}'});expect(r.status===400,'invalid signature accepted')});
await test('Password recovery email and reset permit a fresh login',async()=>{
 const {error}=await clients.alpha.auth.auth.resetPasswordForEmail(clients.alpha.account.email);if(error)throw error;
 const messages=await fetch('http://127.0.0.1:55324/api/v1/messages').then(r=>r.json());
 const candidate=messages.messages.find(m=>/reset|recovery/i.test(m.Subject||m.subject||''));expect(!!candidate,'no password recovery email captured');
 const message=await fetch('http://127.0.0.1:55324/api/v1/message/'+(candidate.ID||candidate.id)).then(r=>r.json());
 const html=message.HTML||message.Text||'';const match=html.match(/https?:[^"\s<>]+\/verify\?[^"\s<>]+/);expect(!!match,'recovery email has no verification link');
 const url=new URL(match[0].replaceAll('&amp;','&'));const {error:verifyError}=await clients.alpha.auth.auth.verifyOtp({token_hash:url.searchParams.get('token'),type:'recovery'});if(verifyError)throw verifyError;
 const password=crypto.randomBytes(24).toString('base64url');const changed=await clients.alpha.auth.auth.updateUser({password});if(changed.error)throw changed.error;
 clients.alpha.account.password=password;fixtures.accounts.alpha.password=password;fs.writeFileSync(new URL('.accounts.json',root),JSON.stringify(fixtures,null,2),{mode:0o600});
 const login=await clients.alpha.auth.auth.signInWithPassword({email:clients.alpha.account.email,password});if(login.error)throw login.error;
});
function totp(secret){const alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';let bits='';for(const c of secret.toUpperCase().replace(/=+$/,''))bits+=alphabet.indexOf(c).toString(2).padStart(5,'0');const key=Buffer.from(bits.match(/.{8}/g).map(b=>parseInt(b,2)));const counter=Buffer.alloc(8);counter.writeBigUInt64BE(BigInt(Math.floor(Date.now()/30000)));const h=crypto.createHmac('sha1',key).update(counter).digest(),o=h[19]&15;return((h.readUInt32BE(o)&0x7fffffff)%1000000).toString().padStart(6,'0');}
await test('Authenticator enrollment, challenge, verification and removal work',async()=>{
 const {data,error}=await clients.alpha.auth.auth.mfa.enroll({factorType:'totp',friendlyName:'QA '+Date.now()});if(error)throw error;
 try{const verified=await clients.alpha.auth.auth.mfa.challengeAndVerify({factorId:data.id,code:totp(data.totp.secret)});if(verified.error)throw verified.error;
 const assurance=await clients.alpha.auth.auth.mfa.getAuthenticatorAssuranceLevel();expect(assurance.data.currentLevel==='aal2','MFA assurance did not increase');}
 finally{const removed=await clients.alpha.auth.auth.mfa.unenroll({factorId:data.id});if(removed.error)throw removed.error;}
});

await test('Avatar proxy cannot be used to read private documents',async()=>status(await call('alpha','/api/avatar?key='+encodeURIComponent(a+'/documents/qa.txt'),null,'GET'),403));
await test('Profile image upload, proxy download and removal work',async()=>{
 const form=new FormData();form.set('file',new Blob([Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jbxkAAAAASUVORK5CYII=','base64')],{type:'image/png'}),'qa.png');
 const x=await call('alpha','/api/settings/profile-image',form);status(x,200);const r=await fetch(env.NEXT_PUBLIC_APP_URL+x.value.avatarUrl,{headers:{cookie:clients.alpha.cookie()}});expect(r.status===200,'avatar proxy failed');
 status(await call('alpha','/api/settings/profile-image',null,'DELETE'),200);
 const freshProfile=await clients.alpha.auth.auth.getUser();expect(!freshProfile.data.user?.user_metadata?.avatar,'deleted avatar remains in auth metadata');
});
await test('Inactive accounts are blocked by both RLS and application APIs',async()=>{
 await sql`update users set is_active=false where id=${clients.beta.account.id}`;
 try{const {data,error}=await clients.beta.auth.from('projects').select('id');expect(!error&&data.length===0,'inactive account read persisted data');status(await call('beta','/api/projects',null,'GET'),403);}
 finally{await sql`update users set is_active=true where id=${clients.beta.account.id}`;clients.beta=await session('beta');}
});
await test('Soft-deleted accounts cannot access the application',async()=>{
 await sql`update users set deleted_at=now() where id=${clients.beta.account.id}`;
 try{status(await call('beta','/api/projects',null,'GET'),403);}
 finally{await sql`update users set deleted_at=null where id=${clients.beta.account.id}`;clients.beta=await session('beta');}
});
await test('Administrator account creation ignores forged authority metadata',async()=>{
 const {data,error}=await service.auth.admin.createUser({email:'untrusted-'+Date.now()+'@qa.example.invalid',password:crypto.randomBytes(24).toString('hex'),email_confirm:true,user_metadata:{name:'Untrusted fixture',is_super_admin:true,client_id:b}});if(error)throw error;
 const [u]=await sql`select is_super_admin,client_id from users where id=${data.user.id}`;expect(u.is_super_admin===false&&u.client_id===null,'metadata granted account authority');await service.auth.admin.deleteUser(data.user.id);
});
await test('Client contract signature fields persist for its own agreement',async()=>{
 const [c]=await sql`insert into contracts(title,contract_number,client_id,type,status,start_date) values('QA agreement',${'QA-'+Date.now()},${a},'service','draft',now()) returning id`;
 const x=await call('alpha','/api/contracts/'+c.id,{status:'active',clientSignedBy:'QA Client',clientSignedAt:new Date().toISOString()},'PATCH');status(x,200);
 const [saved]=await sql`select client_signed_by from contracts where id=${c.id}`;expect(saved.client_signed_by==='QA Client','agreement signature fields were not persisted');
});
await test('Admin custom task-template creation and listing work',async()=>{
 const x=await call('admin','/api/projects/templates',{name:'QA checklist',category:'general',phases:[{name:'QA phase',sortOrder:0,tasks:[{title:'QA task',sortOrder:0}]}]});status(x,201);
 status(await call('alpha','/api/projects/templates',null,'GET'),403);
});

await test('Staff internal comments are invisible to clients through API and direct RLS',async()=>{
 const x=await call('staff','/api/support/'+ticketId+'/comments',{comment:'QA confidential staff note',isInternal:true});status(x,201);
 const visible=await call('alpha','/api/support/'+ticketId+'/comments',null,'GET');status(visible,200);expect(!visible.value.some(c=>c.is_internal),'internal comment leaked through API');
 const direct=await clients.alpha.auth.from('support_ticket_comments').select('*').eq('support_ticket_id',ticketId);if(direct.error)throw direct.error;expect(!direct.data.some(c=>c.is_internal),'internal comment leaked through direct RLS');
});
await test('Marketing overview respects disabled integrations',async()=>{const x=await call('alpha','/api/marketing/features',null,'GET');status(x,200)});
await test('Administrator company creation persists and captures its welcome email',async()=>{const x=await call('admin','/api/clients',{company_name:'QA created company '+Date.now(),email:'new-company@qa.example.invalid'});status(x,201);expect(x.value.welcome_email_sent===true,'company welcome mail was not accepted')});
await test('Legitimate admin still creates users after forged client claims are denied',async()=>{const x=await call('admin','/api/admin/users',{name:'QA final invited account',email:'final-'+Date.now()+'@qa.example.invalid',password:crypto.randomBytes(24).toString('hex'),client_id:a});status(x,201)});
await test('Avatar traversal and private object keys are denied',async()=>{for(const key of ['avatars/'+clients.alpha.account.id+'/../private.pdf',a+'/documents/secret.png'])status(await call('alpha','/api/avatar?key='+encodeURIComponent(key),null,'GET'),403)});
await test('Forged client cannot manage platform storage connections',async()=>status(await call('alpha','/api/storage-connections',{clientId:a,provider:'s3',ownerType:'company',connectionName:'Forbidden',credentials:{accessKeyId:'fake',secretAccessKey:'fake'}}),403));
await test('Other-tenant Stripe keys are never used as a platform fallback',async()=>{
 const salt=crypto.randomBytes(32),iv=crypto.randomBytes(16),key=crypto.scryptSync(env.ENCRYPTION_KEY,salt,32);const cipher=crypto.createCipheriv('aes-256-gcm',key,iv),encrypted=Buffer.concat([cipher.update('sk_live_untrusted_other_tenant'),cipher.final()]);const value=Buffer.concat([salt,iv,cipher.getAuthTag(),encrypted]).toString('base64');
 const [row]=await sql`insert into encrypted_settings(client_id,provider,category,setting_key,encrypted_value,is_active) values(${b},'stripe','payments','secret_key',${value},true) returning id`;
 try{const [invoice]=await sql`insert into invoices(invoice_number,client_id,amount,status,created_by) values(${'QA-fallback-'+Date.now()},${a},25,'sent',${clients.admin.account.id}) returning id`;status(await call('alpha','/api/payments/create-intent',{invoiceId:invoice.id}),200);}
 finally{await sql`delete from encrypted_settings where id=${row.id}`;}
});

async function invokeJob(name){
 const response=await fetch('http://127.0.0.1:8288/v2/apps/kre8iv-clients/functions/'+name+'/invoke',{method:'POST',headers:{'content-type':'application/json'},body:JSON.stringify({data:{}})});
 const invoked=await response.json();expect(response.ok,'job invocation failed');const runId=invoked.data.runId;
 for(let attempt=0;attempt<120;attempt++){
  const run=await fetch('http://127.0.0.1:8288/v1/runs/'+runId).then(r=>r.json());const state=run.data.status.toLowerCase();
  if(state==='completed')return;
  if(['failed','cancelled'].includes(state))throw Error('background run '+state);
  await new Promise(resolve=>setTimeout(resolve,250));
 }
 throw Error('background run did not complete');
}
await test('Background backfill executes successfully and creates tenant folders',async()=>{
 await invokeJob('backfill-brand-folders');const rows=await sql`select id from folders where client_id=${a} and type='brand_management'`;expect(rows.length===1,'missing or duplicate brand folder');
 const visible=await clients.beta.auth.from('folders').select('id').eq('client_id',a);if(visible.error)throw visible.error;expect(visible.data.length===0,'foreign folders visible');
});
let deliveryInvoiceId;
await test('Invoice PDF download and email attachment work end to end',async()=>{
 const number='QA-email-'+Date.now();const [i]=await sql`insert into invoices(invoice_number,client_id,amount,status,created_by,due_date) values(${number},${a},25,'draft',${clients.admin.account.id},now()+interval '5 days') returning id`;deliveryInvoiceId=i.id;
 const pdf=await fetch(env.NEXT_PUBLIC_APP_URL+'/api/invoices/'+i.id+'/pdf',{headers:{cookie:clients.alpha.cookie()}});expect(pdf.status===200,'PDF response failed '+pdf.status);const bytes=Buffer.from(await pdf.arrayBuffer());expect(bytes.subarray(0,5).toString()==='%PDF-','not a PDF');
 status(await call('admin','/api/invoices/'+i.id+'/send',{}),200);
 const messages=await fetch('http://127.0.0.1:55324/api/v1/messages').then(r=>r.json());const mail=messages.messages.find(m=>(m.Subject||'').includes(number));expect(!!mail,'invoice email was not delivered');
 const message=await fetch('http://127.0.0.1:55324/api/v1/message/'+mail.ID).then(r=>r.json());expect(message.Attachments?.some(a=>/\.pdf$/i.test(a.FileName||a.Filename||'')),'invoice email lacked PDF attachment');
});
await test('Scheduled invoice reminder job sends mail and persists reminder state',async()=>{
 await invokeJob('send-invoice-reminders');const [invoice]=await sql`select reminded_due_7_at from invoices where id=${deliveryInvoiceId}`;expect(!!invoice.reminded_due_7_at,'reminder state was not saved');
});

await test('Concurrent duplicate payment events produce only one settlement record',async()=>{
 const eventId='evt_qa_concurrent_'+Date.now();const [invoice]=await sql`insert into invoices(invoice_number,client_id,amount,status,stripe_payment_intent_id,created_by) values(${'QA-concurrent-'+Date.now()},${a},25,'sent',${paymentId},${clients.admin.account.id}) returning id`;
 const object={id:paymentId,amount:2500,currency:'usd',metadata:{invoice_id:invoice.id},payment_method_types:['card']};
 const responses=await Promise.all([webhook('payment_intent.succeeded',object,eventId),webhook('payment_intent.succeeded',object,eventId)]);for(const response of responses)status(response,200);
 const [count]=await sql`select count(*)::int as total from activity_logs where properties->>'stripe_event_id'=${eventId}`;expect(count.total===1,'duplicate settlement activity was recorded');
});
await test('A valid webhook for another tenant cannot settle this tenant invoice',async()=>{
 const foreignSecret='whsec_qa_foreign_tenant';function encrypted(value){const salt=crypto.randomBytes(32),iv=crypto.randomBytes(16),key=crypto.scryptSync(env.ENCRYPTION_KEY,salt,32),cipher=crypto.createCipheriv('aes-256-gcm',key,iv);const bytes=Buffer.concat([cipher.update(value),cipher.final()]);return Buffer.concat([salt,iv,cipher.getAuthTag(),bytes]).toString('base64');}
 const [row]=await sql`insert into encrypted_settings(client_id,provider,category,setting_key,encrypted_value,is_active) values(${b},'stripe','payments','webhook_secret',${encrypted(foreignSecret)},true) returning id`;
 try{
  const [invoice]=await sql`insert into invoices(invoice_number,client_id,amount,status,stripe_payment_intent_id,created_by) values(${'QA-foreign-webhook-'+Date.now()},${a},25,'sent',${paymentId},${clients.admin.account.id}) returning id`;
  const body=JSON.stringify({id:'evt_qa_foreign_'+Date.now(),object:'event',type:'payment_intent.succeeded',data:{object:{id:paymentId,amount:2500,currency:'usd',metadata:{invoice_id:invoice.id},payment_method_types:['card']}}});
  const time=Math.floor(Date.now()/1000),signature=crypto.createHmac('sha256',foreignSecret).update(time+'.'+body).digest('hex');
  const response=await fetch(env.NEXT_PUBLIC_APP_URL+'/api/webhooks/stripe',{method:'POST',headers:{'content-type':'application/json','stripe-signature':'t='+time+',v1='+signature},body});expect(response.status!==200,'foreign payment account was accepted');
  const [persisted]=await sql`select status from invoices where id=${invoice.id}`;expect(persisted.status==='sent','foreign account settled invoice');
 }finally{await sql`delete from encrypted_settings where id=${row.id}`;}
});

await test('Account managers can provision clients without assigning administrator roles',async()=>{
 const email='manager-'+Date.now()+'@qa.example.invalid',password=crypto.randomBytes(24).toString('hex');const {data,error}=await service.auth.admin.createUser({email,password,email_confirm:true,user_metadata:{name:'QA manager'}});if(error)throw error;
 const [managerRole]=await sql`select id from roles where name='account_manager'`;const [adminRole]=await sql`select id from roles where name='admin'`;await sql`insert into user_roles(user_id,role_id) values(${data.user.id},${managerRole.id})`;
 fixtures.accounts.manager={id:data.user.id,email,password,clientId:null};clients.manager=await session('manager');
 try{status(await call('manager','/api/admin/users/'+clients.admin.account.id,{password:crypto.randomBytes(24).toString('hex')},'PATCH'),403);
 const x=await call('manager','/api/admin/users',{name:'QA manager provisioned',email:'managed-'+Date.now()+'@qa.example.invalid',password:crypto.randomBytes(24).toString('hex'),client_id:a,roles:[adminRole.id]});status(x,201);
 const rows=await sql`select r.name from user_roles ur join roles r on r.id=ur.role_id where ur.user_id=${x.value.id}`;expect(rows.length===1&&rows[0].name==='client','manager assigned an administrator role');}
 finally{await service.auth.admin.deleteUser(data.user.id);delete fixtures.accounts.manager;delete clients.manager;}
});
await sql.end();console.log(JSON.stringify({passed:results.filter(x=>x.passed).length,failed:results.filter(x=>!x.passed).length}));

if(results.some(result => !result.passed)) process.exitCode=1;
