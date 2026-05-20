document.addEventListener('DOMContentLoaded', function(){
  // inject header if not present
  if (!document.querySelector('.site-header')){
    const header = document.createElement('div'); header.className='site-header card pop';
    header.innerHTML = `
      <div class="site-brand">
        <div class="brand-logo">A</div>
        <div>
          <div class="brand-title">Apprends les Articles</div>
          <div class="muted">Amusant • Simple • Rapide</div>
        </div>
      </div>
      <div class="header-actions">
        <button id="toggleTheme" class="btn ghost" title="Toggle dark mode">🌗</button>
        <a href="/student/index.php" class="btn secondary">Mon espace</a>
      </div>
    `;
    document.body.insertBefore(header, document.body.firstChild);
  }

  // inject footer
  if (!document.querySelector('.site-footer')){
    const f = document.createElement('div'); f.className='site-footer muted';
    f.innerHTML = '<div class="app-container">&copy; '+new Date().getFullYear()+" — Apprends les Articles. Bonne chance !</div>";
    document.body.appendChild(f);
  }

  // theme toggle
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

  // lightweight success sound
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

  // auto-enhance buttons
  document.querySelectorAll('button').forEach(b=>{
    if (!b.classList.contains('btn')) b.classList.add('btn','ghost');
  });

});
