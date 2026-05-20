// public assets copy of ui.js (loads in browser)
// Ensure there's always a safe `playSuccess` function available in case
// `showLevelUp` is invoked before DOMContentLoaded (server may inject a call).
if (typeof window.playSuccess !== 'function') {
  window.playSuccess = function(){ /* no-op until DOM ready */ };
}

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

  // initialize any xp bars on the page
  document.querySelectorAll('.xp-bar').forEach((el)=>{
    const xp = parseInt(el.getAttribute('data-xp')||0,10);
    const level = parseInt(el.getAttribute('data-level')||1,10);
    const xpForLevel = 100 * Math.pow(level, 1.25);
    const xpForNext = 100 * Math.pow(level+1, 1.25);
    const percent = Math.max(0, Math.min(100, Math.round(((xp - xpForLevel) / (xpForNext - xpForLevel)) * 100)));
    let labelEl = el.nextElementSibling && el.nextElementSibling.classList && el.nextElementSibling.classList.contains('xp-bar__label') ? el.nextElementSibling : null;
    // fallback: look for element with id like '{barId}Label' or class with data-for attribute
    if (!labelEl && el.id) {
        const candidates = [];
        candidates.push(el.id + 'Label');
        // if id ends with 'Bar', try without
        if (/Bar$/.test(el.id)) candidates.push(el.id.replace(/Bar$/, '') + 'Label');
        // also try simple 'Label' suffix
        candidates.push(el.id.replace(/Bar$/, '') + 'BarLabel');
        for (const cid of candidates){
          const found = document.getElementById(cid);
          if (found){ labelEl = found; break; }
        }
        if (!labelEl) labelEl = document.querySelector('.xp-bar__label[data-for="'+el.id+'"]');
        // also allow searching within parent container
        if (!labelEl && el.parentElement) labelEl = el.parentElement.querySelector('.xp-bar__label');
    }
    const labelText = `Niveau ${level} — ${xp} XP`;
    if (labelEl) labelEl.textContent = labelText;
    updateXPBar(el, percent, labelText);
  });
    // debug flag to indicate initialization finished
    try{ window.__xp_bar_init_done = true; }catch(e){}

});

// --- Gamification helpers ---
window.updateXPBar = function(containerSelectorOrElement, percent, label){
  const container = (typeof containerSelectorOrElement === 'string') ? document.querySelector(containerSelectorOrElement) : containerSelectorOrElement;
  if (!container) return;
  let fill = container.querySelector('.xp-bar__fill');
  if (!fill){
    fill = document.createElement('div'); fill.className='xp-bar__fill';
    container.appendChild(fill);
  }
  // clamp and animate
  const p = Math.max(0, Math.min(100, percent));
  requestAnimationFrame(()=> fill.style.width = p + '%');
  const lbl = container.nextElementSibling;
  if (lbl && label) lbl.textContent = label;
};

  window.showLevelUp = function({level, newXP, rewardText}){
  // create modal if missing
  let backdrop = document.querySelector('.modal-backdrop');
  if (backdrop) backdrop.remove();
  backdrop = document.createElement('div'); backdrop.className='modal-backdrop';
  const modal = document.createElement('div'); modal.className='modal level-up pop';
  modal.innerHTML = `<div class="level-up">
    <div class="level-badge">${level}</div>
    <h2>Bravo ! Niveau ${level}</h2>
    <p class="muted">${rewardText || 'Tu as débloqué des récompenses !'}</p>
    <button class="btn" id="levelClose">Continuer</button>
  </div>`;
  // ensure clicks inside modal don't bubble to backdrop
  modal.addEventListener('click', function(e){ e.stopPropagation(); });
  backdrop.appendChild(modal); document.body.appendChild(backdrop);
  if (typeof window.playSuccess === 'function') window.playSuccess();
  // confetti canvas
  // try Lottie level animation, fallback to confetti
  if (window.lottie){
    try{
      const container = modal.querySelector('.level-badge');
      container.innerHTML = '<div id="lottieLevel" style="width:120px;height:120px"></div>';
      lottie.loadAnimation({container: document.getElementById('lottieLevel'), renderer:'svg', loop:false, autoplay:true, path:'/assets/animations/levelup.json'});
    }catch(e){ triggerConfetti(); }
  }else{ triggerConfetti(); }
  // try to unlock a level badge via API
  try{
    fetch('/api/unlock_badge.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'code=level_'+level})
      .then(r=>r.json()).then(j=>{ if (j && j.badge){
        const b = j.badge; const toast = document.createElement('div'); toast.className='toast card'; toast.textContent = 'Badge débloqué: '+b.title; document.body.appendChild(toast); setTimeout(()=>toast.remove(),3000);
      }}).catch(()=>{});
  }catch(e){}
  // attach close handlers: button, backdrop click, and Escape key
  const closeBtn = modal.querySelector('#levelClose');
  if (closeBtn){ closeBtn.addEventListener('click', ()=> backdrop.remove()); }
  backdrop.addEventListener('click', ()=> backdrop.remove());
  const escHandler = (ev)=>{ if (ev.key === 'Escape') { backdrop.remove(); window.removeEventListener('keydown', escHandler); } };
  window.addEventListener('keydown', escHandler);
};

// Global delegated handlers: ensure modal can be closed even if created before this script ran
document.addEventListener('click', function(e){
  // close button with id
  const btn = e.target.closest('#levelClose');
  if (btn){
    const bd = document.querySelector('.modal-backdrop'); if (bd) bd.remove(); return;
  }
  // click on backdrop itself
  if (e.target && e.target.classList && e.target.classList.contains('modal-backdrop')){
    e.target.remove(); return;
  }
  // any element with data-modal-close attribute
  const closish = e.target.closest('[data-modal-close]');
  if (closish){ const bd = document.querySelector('.modal-backdrop'); if (bd) bd.remove(); }
});

document.addEventListener('keydown', function(ev){ if (ev.key === 'Escape'){ const bd = document.querySelector('.modal-backdrop'); if (bd) bd.remove(); } });

window.triggerConfetti = function(){
  // simple confetti using DOM elements
  const c = document.createElement('div'); c.className='confetti-canvas';
  document.body.appendChild(c);
  for (let i=0;i<24;i++){
    const el = document.createElement('div');
    el.style.position='absolute'; el.style.width='10px'; el.style.height='14px';
    el.style.background=['#FFD166','#EF476F','#06D6A0','#3D8AF7'][i%4];
    el.style.left=(50 + (Math.random()*80-40))+'%'; el.style.top='30%'; el.style.opacity='0.95';
    el.style.transform=`translateY(0) rotate(${Math.random()*360}deg)`;
    el.style.borderRadius='2px'; el.style.zIndex='4000';
    c.appendChild(el);
    const dx = (Math.random()*160-80); const dy = 400 + Math.random()*160; const rot = (Math.random()*720-360);
    el.animate([
      {transform:`translateY(0) rotate(${rot}deg)`, opacity:1},
      {transform:`translate(${dx}px, ${dy}px) rotate(${rot+360}deg)`, opacity:0}
    ], {duration:900 + Math.random()*700, easing:'cubic-bezier(.2,.9,.2,1)'});
  }
  setTimeout(()=>{ const d = document.querySelector('.confetti-canvas'); if (d) d.remove(); },1600);
};

// Badge gallery
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('openBadges');
  if (!btn) return;
  btn.addEventListener('click', async ()=>{
    try{
      const res = await fetch('/api/list_badges.php'); const j = await res.json();
      if (!j || !j.badges) return;
      // build modal
      let backdrop = document.querySelector('.modal-backdrop'); if (backdrop) backdrop.remove();
      backdrop = document.createElement('div'); backdrop.className='modal-backdrop';
      const modal = document.createElement('div'); modal.className='modal pop';
      let html = '<h3>Galerie de badges</h3><div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:12px">';
      j.badges.forEach(b=>{
        const owned = b.awarded_at ? true : false;
        const icon = b.icon || '/assets/icons/badge_placeholder.png';
        // image fallback: if PNG missing, try SVG same-name, final fallback is placeholder
        const safeSrc = icon;
        const onerr = `this.onerror=null; this.src=this.src.replace(/\.png$/,'.svg'); this.onerror=function(){this.src='/assets/icons/badge_placeholder.png'};`;
        html += `<div style="width:140px;padding:12px;border-radius:12px;background:${owned?'linear-gradient(90deg,#f0fff4,#fff)':'#fff8f0'};box-shadow:var(--shadow-sm);text-align:center">`;
        html += `<div style="height:64px;margin-bottom:8px"><img src="${safeSrc}" onerror="${onerr}" alt="" style="max-height:64px"></div>`;
        html += `<strong>${b.title}</strong><div class="muted" style="font-size:13px">${owned? 'Débloqué' : 'Bloqué'}</div></div>`;
      });
      html += '</div><div style="margin-top:12px;text-align:center"><button class="btn" id="closeBadges">Fermer</button></div>';
      modal.innerHTML = html; backdrop.appendChild(modal); document.body.appendChild(backdrop);
      document.getElementById('closeBadges').addEventListener('click', ()=>backdrop.remove());
    }catch(e){ console.error(e); }
  });
});

// Open chest helper (used in dashboard)
window.openChest = async function(){
  try{
    const res = await fetch('/api/open_chest.php', {method:'POST'});
    const j = await res.json();
    if (!j || !j.ok) return;
    // show chest modal
    let backdrop = document.querySelector('.modal-backdrop'); if (backdrop) backdrop.remove();
    backdrop = document.createElement('div'); backdrop.className='modal-backdrop';
    const modal = document.createElement('div'); modal.className='modal pop';
    modal.innerHTML = `<div class="level-up"><div class="chest"></div><h2>Tu as gagné ${j.xp} XP !</h2><p class="muted">${j.badge? 'Badge: '+j.badge : ''}</p><div style="margin-top:12px"><button class="btn" id="closeChest">OK</button></div></div>`;
    backdrop.appendChild(modal); document.body.appendChild(backdrop);
    document.getElementById('closeChest').addEventListener('click', ()=>backdrop.remove());
      // try Lottie chest animation
      if (window.lottie){
        try{
          const ctn = modal.querySelector('.chest'); ctn.innerHTML = '<div id="lottieChest" style="width:140px;height:140px;margin:0 auto"></div>';
          lottie.loadAnimation({container: document.getElementById('lottieChest'), renderer:'svg', loop:false, autoplay:true, path:'/assets/animations/chest.json'});
        }catch(e){ /* ignore */ }
      }
  }catch(e){ console.error(e); }
};
