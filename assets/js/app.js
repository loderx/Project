const t=document.getElementById('themeBtn');
if(t){t.addEventListener('click',async()=>{const dark=document.body.classList.contains('theme-dark');const theme=dark?'light':'dark';await fetch('?page=api_toggle_theme',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'theme='+theme});location.reload();});}
const f=document.getElementById('eventForm');
if(f){f.addEventListener('submit',async(e)=>{e.preventDefault();const fd=new URLSearchParams(new FormData(f));const r=await fetch('?page=api_add_event',{method:'POST',body:fd});const j=await r.json();document.getElementById('eventMsg').innerText=j.ok?'Saved!':'Error';if(j.ok)location.reload();});}
