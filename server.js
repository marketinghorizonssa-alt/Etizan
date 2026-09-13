'use strict';
const express=require('express');
const path=require('path');
const crypto=require('crypto');
require('./lib/env')();
const {ROUTER,TOKEN,RELEASE,services,pages}=require('./lib/config');
const {html}=require('./lib/render');
const app=express();
app.disable('x-powered-by');
app.set('trust proxy',1);
app.use(express.json({limit:'32kb'}));
app.use('/assets',express.static(path.join(__dirname,'public/assets'),{maxAge:'1y',immutable:true}));
app.get('/healthz',(req,res)=>res.set('Cache-Control','no-store').json({ok:true,service:'etizan-law',release:RELEASE,router_configured:Boolean(TOKEN)}));
app.post('/submit',async(req,res)=>{
 try{
  if(!TOKEN)return res.status(503).json({ok:false,error:'router_not_configured'});
  const d=req.body||{},name=String(d.name||'').trim(),digits=String(d.phone||'').replace(/\D/g,'');
  if(name.length<2||digits.length<9)return res.status(422).json({ok:false,error:'validation'});
  const city=['الرياض','جدة'].includes(d.city)?d.city:'الرياض';
  const service=services.includes(d.service)?d.service:'خدمات قانونية أخرى';
  const payload={name,phone:String(d.phone||''),city,service,message:String(d.message||'').slice(0,1600),preferred_contact:'غير محدد',consent:'نعم',consent_version:'v1',consent_at:new Date().toISOString(),submitted_at:new Date().toISOString(),website_submission_id:String(d.website_submission_id||`ETZ-WEB-${Date.now()}-${crypto.randomBytes(3).toString('hex')}`),page_url:String(d.page_url||'').slice(0,1000),referrer:String(d.referrer||'').slice(0,1000)};
  for(const k of ['gclid','gbraid','wbraid','utm_source','utm_medium','utm_campaign','utm_term','utm_content','campaign_id','adgroup_id','creative_id'])payload[k]=String(d[k]||'').slice(0,500);
  const r=await fetch(`${ROUTER}?token=${encodeURIComponent(TOKEN)}`,{method:'POST',headers:{'content-type':'application/json'},body:JSON.stringify(payload),redirect:'follow',signal:AbortSignal.timeout(12000)});
  const text=await r.text(); let j={}; try{j=JSON.parse(text)}catch{}
  if(!r.ok||!j.ok)return res.status(502).json({ok:false,error:'upstream'});
  return res.json({ok:true,lead_id:j.lead_id||null});
 }catch(e){console.error('submit_error',e?.message||e);return res.status(502).json({ok:false,error:'upstream'});}
});
app.get('/robots.txt',(req,res)=>res.type('text/plain').send('User-agent: *\nAllow: /\nSitemap: https://etizan.hositee.com/sitemap.xml\n'));
app.get('/sitemap.xml',(req,res)=>{const routes=[];for(const c of ['riyadh','jeddah'])for(const k of Object.keys(pages))routes.push(`https://etizan.hositee.com/${c}/${k==='general'?'':k+'/'}`);res.type('application/xml').send(`<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${routes.map(u=>`<url><loc>${u}</loc></url>`).join('')}</urlset>`)});
app.get('/',(req,res)=>res.redirect(302,'/riyadh/'));
app.get(/^\/(riyadh|jeddah)(?:\/([^/]+))?\/?$/,(req,res)=>{
 const parts=req.path.split('/').filter(Boolean),key=parts[1]||'general';
 if(key!=='general'&&!pages[key])return res.status(404).type('text/plain').send('Not Found');
 res.set({'Cache-Control':'public, max-age=300','X-Content-Type-Options':'nosniff','Referrer-Policy':'strict-origin-when-cross-origin','X-Frame-Options':'SAMEORIGIN','Permissions-Policy':'camera=(), microphone=(), geolocation=()'});
 res.send(html(req.path));
});
app.use((req,res)=>res.status(404).type('text/plain').send('Not Found'));
const port=process.env.PORT||3000;
app.listen(port,'0.0.0.0',()=>console.log(`Etizan landing listening on ${port}`));
