import http from 'node:http';
const server=http.createServer(async(req,res)=>{
 if(req.method!=='POST'||req.url!=='/v1/chat/completions'){res.writeHead(404);return res.end()}
 let raw='';for await(const chunk of req){raw+=chunk;if(raw.length>300000){res.writeHead(413);return res.end()}}
 if(req.headers.authorization!=='Bearer isolated-simulator'){res.writeHead(401);return res.end()}
 const body=JSON.parse(raw),prompt=body.messages?.find(m=>m.role==='user')?.content||'';
 if(prompt.includes('SIMULATE_PROVIDER_ERROR')){res.writeHead(503,{'content-type':'application/json'});return res.end(JSON.stringify({error:{message:'simulated unavailable'}}))}
 const name=prompt.match(/^TOOL: (.+)/)?.[1]||'Marketing';
 const artifact={title:name+' — isolated QA draft',analysis:'SIMULATED OUTPUT. This verifies application routing, client isolation, validation, persistence and display. No live account data or real AI analysis was used.',recommendations:['Review the supplied methodology and gather the account exports required by the selected tool.'],missingData:['Real provider acceptance and client evidence are still required.'],sources:['Synthetic isolated test fixture']};
 res.writeHead(200,{'content-type':'application/json'});res.end(JSON.stringify({choices:[{message:{content:prompt.includes('SIMULATE_INVALID_ARTIFACT')?'not valid JSON':JSON.stringify(artifact)}}],usage:{prompt_tokens:100,completion_tokens:100,total_tokens:200}}));
});
server.listen(3102,'127.0.0.1',()=>console.log('Isolated marketing AI simulator on 127.0.0.1:3102'));
