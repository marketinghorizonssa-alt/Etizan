(()=>{
  const dl=(event,extra={})=>{window.dataLayer=window.dataLayer||[];window.dataLayer.push({event,...extra})};
  const qs=new URLSearchParams(location.search);
  const attr={}; ['gclid','gbraid','wbraid','utm_source','utm_medium','utm_campaign','utm_term','utm_content','campaign_id','adgroup_id','creative_id'].forEach(k=>{if(qs.get(k))attr[k]=qs.get(k)});

  const menu=document.querySelector('.landing-menu');
  document.addEventListener('click',e=>{
    if(menu?.open&&!menu.contains(e.target))menu.removeAttribute('open');
    const a=e.target.closest?.('a.track-wa,a.track-call');
    if(!a)return;
    const event=a.classList.contains('track-wa')?'whatsapp_click':'phone_click';
    dl(event,{contact_city:document.body.dataset.city,contact_service:document.body.dataset.service,page_path:location.pathname,...attr});
  },{passive:true});
  menu?.addEventListener('keydown',e=>{if(e.key==='Escape')menu.removeAttribute('open')});

  const f=document.getElementById('leadForm'),status=document.getElementById('formStatus'),select=document.getElementById('serviceSelect');
  if(!f)return;
  const phone=f.elements.phone;
  if(phone){phone.dir='ltr';phone.style.textAlign='left';phone.maxLength=25;phone.placeholder='05xxxxxxxx / +966... / +20...';}
  select.addEventListener('change',()=>{f.elements.service.value=select.value});
  f.addEventListener('submit',async e=>{
    e.preventDefault(); status.className='form-status'; status.textContent='';
    if(!f.reportValidity())return;
    if(!document.getElementById('consentCheck').checked){status.textContent='الموافقة على سياسة الخصوصية مطلوبة.';status.classList.add('bad');return}
    const btn=f.querySelector('button[type=submit]'); btn.disabled=true; btn.textContent='جارٍ الإرسال…';
    const fd=new FormData(f); const p=Object.fromEntries(fd.entries());
    p.service=select.value; p.page_url=location.href; p.referrer=document.referrer; Object.assign(p,attr);
    p.website_submission_id='ETZ-WEB-'+Date.now()+'-'+Math.random().toString(36).slice(2,9);
    try{
      const r=await fetch('/submit',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(p)});
      let j={}; try{j=await r.json()}catch(_){ }
      if(!r.ok||!j.ok){
        if(r.status===422)throw new Error('validation');
        throw new Error('submit');
      }
      dl('form_submit_success',{lead_id:j.lead_id||'',contact_city:p.city,contact_service:p.service,page_path:location.pathname,...attr});
      status.textContent='تم استلام طلبك بنجاح. سيتم التواصل معك بعد مراجعة البيانات.'; status.classList.add('ok');
      f.reset(); document.getElementById('consentCheck').checked=true; select.value=document.body.dataset.service; f.elements.service.value=document.body.dataset.service;
    }catch(err){
      status.textContent=err?.message==='validation'?'يرجى التأكد من الاسم ورقم الجوال. يقبل الرقم المحلي أو الدولي.':'تعذر إرسال الطلب الآن. يمكنك التواصل عبر واتساب مباشرة.';
      status.classList.add('bad');
    }
    finally{btn.disabled=false;btn.textContent='إرسال الطلب'}
  });
})();
