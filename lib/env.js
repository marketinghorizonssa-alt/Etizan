'use strict';
const fs=require('fs');
const path=require('path');
module.exports=function loadEnv(){
 try{
  const p=path.join(__dirname,'..','.env');
  if(!fs.existsSync(p))return;
  for(const raw of fs.readFileSync(p,'utf8').split(/\r?\n/)){
   const line=raw.trim();
   if(!line||line.startsWith('#'))continue;
   const i=line.indexOf('=');
   if(i<1)continue;
   const key=line.slice(0,i).trim();
   let value=line.slice(i+1).trim();
   if((value.startsWith('"')&&value.endsWith('"'))||(value.startsWith("'")&&value.endsWith("'")))value=value.slice(1,-1);
   if(process.env[key]===undefined)process.env[key]=value;
  }
 }catch(e){console.error('env_load_error',e?.message||e);}
};
