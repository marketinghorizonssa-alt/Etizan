(()=>{
  const dl=(event,extra={})=>{window.dataLayer=window.dataLayer||[];window.dataLayer.push({event,...extra})};
  const qs=new URLSearchParams(location.search);
  const attr={}; ['gclid','gbraid','wbraid','utm_source','utm_medium','utm_campaign','utm_term','utm_content','campaign_id','adgroup_id','creative_id'].forEach(k=>{if(qs.get(k))attr[k]=qs.get(k)});

  const landingPages=[
    ['', 'الرئيسية'],
    ['corporate','الشركات والاستثمار'],
    ['contracts','التجاري والعقود'],
    ['litigation','القضايا والتقاضي'],
    ['criminal','القضايا الجنائية'],
    ['administrative','القضايا الإدارية'],
    ['real-estate','العقارات'],
    ['labor','القضايا العمالية'],
    ['family-inheritance','الأحوال والتركات'],
    ['enforcement-arbitration','التنفيذ والتحكيم'],
    ['insurance','التأمين'],
    ['finance-regulatory','المصرفي والضريبي'],
    ['bankruptcy','الإفلاس'],
    ['ip-franchise','الملكية الفكرية والامتياز'],
    ['cybercrime','الجرائم المعلوماتية'],
    ['aviation-transport','الطيران والنقل']
  ];

  const nav=document.querySelector('.nav-links');
  if(nav){
    const currentCity=location.pathname.startsWith('/jeddah')?'jeddah':'riyadh';
    const branchColumn=(slug,label)=>`<section class="branch-menu"><h4><a href="/${slug}/">${label}</a></h4>${landingPages.map(([path,name],i)=>`<a${i===0?' class="branch-home"':''} href="/${slug}/${path?path+'/':''}">${name}</a>`).join('')}</section>`;
    nav.classList.add('campaign-nav');
    nav.innerHTML=`<a class="current-home" href="/${currentCity}/">الرئيسية</a><details class="landing-menu"><summary>الأقسام <span aria-hidden="true">⌄</span></summary><div class="landing-mega"><div class="landing-mega-grid">${branchColumn('riyadh','الرياض')}${branchColumn('jeddah','جدة')}</div></div></details><a href="#about">من نحن</a><a href="#why">لماذا إتزان</a><a href="#process">آلية العمل</a>`;
    const menu=nav.querySelector('.landing-menu');
    document.addEventListener('click',e=>{if(menu?.open&&!menu.contains(e.target))menu.removeAttribute('open')});
    menu?.addEventListener('keydown',e=>{if(e.key==='Escape')menu.removeAttribute('open')});
  }

  const office=document.querySelector('.office-location');
  if(office){
    let query=office.textContent.trim();
    try{query=new URL(office.href).searchParams.get('query')||query}catch(_){ }
    const map=document.createElement('div');
    map.className='office-map';
    map.innerHTML=`<iframe title="موقع ${document.body.dataset.city||'المكتب'} على خرائط Google" src="https://www.google.com/maps?q=${encodeURIComponent(query)}&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>`;
    office.insertAdjacentElement('afterend',map);
  }

  document.querySelectorAll('.track-wa').forEach(a=>a.addEventListener('click',()=>dl('whatsapp_click',{contact_city:document.body.dataset.city,contact_service:document.body.dataset.service,page_path:location.pathname,...attr}),{passive:true}));
  document.querySelectorAll('.track-call').forEach(a=>a.addEventListener('click',()=>dl('phone_click',{contact_city:document.body.dataset.city,contact_service:document.body.dataset.service,page_path:location.pathname,...attr}),{passive:true}));
  const f=document.getElementById('leadForm'),status=document.getElementById('formStatus'),select=document.getElementById('serviceSelect');
  if(!f)return;
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
      const r=await fetch('/submit',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(p)}); const j=await r.json();
      if(!r.ok||!j.ok)throw new Error('submit');
      dl('form_submit_success',{lead_id:j.lead_id||'',contact_city:p.city,contact_service:p.service,page_path:location.pathname,...attr});
      status.textContent='تم استلام طلبك بنجاح. سيتم التواصل معك بعد مراجعة البيانات.'; status.classList.add('ok');
      f.reset(); document.getElementById('consentCheck').checked=true; select.value=document.body.dataset.service; f.elements.service.value=document.body.dataset.service;
    }catch(_){status.textContent='تعذر إرسال الطلب الآن. يمكنك التواصل عبر واتساب مباشرة.';status.classList.add('bad')}
    finally{btn.disabled=false;btn.textContent='إرسال الطلب'}
  });
})();
