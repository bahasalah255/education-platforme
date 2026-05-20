// public assets copy of ui.js (loads in browser)
document.addEventListener('DOMContentLoaded', function(){
  // don't inject header here; server-side partial handles header now

  // footer injection if missing
  if (!document.querySelector('.site-footer')){
    const f = document.createElement('div'); f.className='site-footer muted';
    f.innerHTML = '<div class="app-container">&copy; '+new Date().getFullYear()+" — Apprends les Articles. Bonne chance !</div>";
    document.body.appendChild(f);
  }

  // theme toggle handler (button inserted by server header)
  const btn = document.getElementById('toggleTheme');
  if (btn){
    const saved = localStorage.getItem('ui-theme');
    if (saved === 'dark') document.body.classList.add('dark');
    btn.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('ui-theme', document.body.classList.contains('dark') ? 'dark' : 'light');
      btn.classList.add('pop'); setTimeout(()=>btn.classList.remove('pop'),350);
    });
  }

  window.playSuccess = function(){
    try{
      const ctx = new (window.AudioContext||window.webkitAudioContext)();
      const o = ctx.createOscillator(); const g = ctx.createGain();
      o.type='sine'; o.frequency.value=880; g.gain.value=0.001;
      o.connect(g); g.connect(ctx.destination); o.start();
      g.gain.exponentialRampToValueAtTime(0.02, ctx.currentTime+0.02);
      g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime+0.3);
      setTimeout(()=>{ o.stop(); ctx.close(); },400);
    }catch(e){/* ignore */}
  }

  document.querySelectorAll('button').forEach(b=>{
    if (!b.classList.contains('btn')) b.classList.add('btn','ghost');
  });

});
