import fs from "node:fs";
import crypto from "node:crypto";
import { createRequire } from "node:module";
const require=createRequire(new URL("../../package.json",import.meta.url));
const { createClient }=require("@supabase/supabase-js");
const postgres=require("postgres");
const root=new URL("../",import.meta.url);
if(fs.existsSync(new URL('.accounts.json',root))){console.log('Synthetic accounts already exist; preserving fixtures.');process.exit(0);}
const env=Object.fromEntries(fs.readFileSync(new URL('.env.staging',root),'utf8').trim().split('\n').map(l=>{const i=l.indexOf('=');return [l.slice(0,i),l.slice(i+1)]}));
if(new URL(env.NEXT_PUBLIC_SUPABASE_URL).port!=='55321')throw Error('Staging API required');
const admin=createClient(env.NEXT_PUBLIC_SUPABASE_URL,env.SUPABASE_SERVICE_ROLE_KEY,{auth:{persistSession:false}});
const sql=postgres(env.DATABASE_URL,{max:1});
const roleIds={};
for(const name of ['super_admin','admin','account_manager','staff','client']){
 const r=await sql`insert into roles(name,is_system) values(${name},true) on conflict(name) do update set is_system=true returning id`;
 roleIds[name]=r[0].id;
}
const permissionSource=fs.readFileSync(new URL('../../lib/rbac/permissions.ts',import.meta.url),'utf8');
const permissions=[...permissionSource.matchAll(/\w+: "([\w-]+\.[\w.-]+)"/g)].map(m=>m[1]);
for(const name of new Set(permissions)){
 const [resource,action]=name.split('.');
 const [p]=await sql`insert into permissions(name,resource,action) values(${name},${resource},${action}) on conflict(name) do update set resource=excluded.resource returning id`;
 for(const role of ['admin','super_admin'])await sql`insert into role_permissions(role_id,permission_id) values(${roleIds[role]},${p.id}) on conflict do nothing`;
 if(/^(requests|documents|support|proposals|invoices|contracts|projects)\./.test(name)&&!/(delete|assign|manage)/.test(name))await sql`insert into role_permissions(role_id,permission_id) values(${roleIds.client},${p.id}) on conflict do nothing`;
}
const clients={};for(const tenant of ['alpha','beta']){
 const email=tenant+'@client-qa.example.invalid';
 const [c]=await sql`insert into clients(company_name,email,status) values(${'QA '+tenant},${email},'active') returning id`;
 clients[tenant]=c.id;
}
const accounts={};for(const [name,role,tenant] of [['admin','admin',null],['staff','staff',null],['alpha','client','alpha'],['beta','client','beta']]){
 const password=crypto.randomBytes(24).toString('base64url'),email=name+'@client-qa.example.invalid';
 const {data,error}=await admin.auth.admin.createUser({email,password,email_confirm:true,user_metadata:{name:'QA '+name}});if(error)throw error;
 const id=data.user.id;
 await sql`update users set client_id=${tenant?clients[tenant]:null},is_super_admin=${name==='admin'},name=${'QA '+name} where id=${id}`;
 await sql`insert into user_roles(user_id,role_id) values(${id},${roleIds[role]})`;
 accounts[name]={id,email,password,clientId:tenant?clients[tenant]:null};
}
for(const [key,value] of Object.entries({provider:'smtp',smtp_host:'127.0.0.1',smtp_port:'55325',smtp_encryption:'none',from_email:'portal@client-qa.example.invalid',from_name:'Client QA',notification_email:'admin@client-qa.example.invalid'})){
 await sql`insert into system_settings(category,key,value,is_encrypted) values('email',${key},${value},false) on conflict(category,key) do update set value=excluded.value`;
}
for(const tenant of ['alpha','beta'])await sql`insert into projects(client_id,name,description,status) values(${clients[tenant]},${'QA '+tenant+' project'},'Synthetic project for tenant isolation tests','active')`;
await sql`insert into service_templates(name,is_active,currency,line_items,total_amount,created_by) values('QA Design Service',true,'USD','[{"description":"Synthetic design work","quantity":1,"unitPrice":25,"amount":25}]',25,${accounts.admin.id})`;
await sql.unsafe(fs.readFileSync(new URL('../../lib/db/migrations/028_email_template_catalog.sql',import.meta.url),'utf8'));
for (const type of ['invoice_sent','invoice_reminder','invoice_overdue']) {
 await sql`insert into email_templates(name,type,subject,html_content,text_content,is_default,is_active,available_variables)
 values(${'QA '+type},${type},'Invoice {{invoice_number}}','<h1>Invoice {{invoice_number}}</h1><p>{{company_name}}</p><p>{{amount}}</p>','Invoice {{invoice_number}}',true,true,'[]'::jsonb)`;
}
await admin.storage.createBucket('documents',{public:false});
await admin.storage.createBucket('avatars',{public:true});
fs.writeFileSync(new URL('.accounts.json',root),JSON.stringify({accounts,clients},null,2),{mode:0o600});
console.log('Seeded two synthetic tenants, admin/staff/client roles, isolated mail capture and storage buckets.');
await sql.end();
