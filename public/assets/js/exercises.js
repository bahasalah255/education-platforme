document.addEventListener('DOMContentLoaded', function(){
  // MCQ UI: enhance selectable labels
  document.querySelectorAll('form[data-etype="mcq"]').forEach(form=>{
    form.querySelectorAll('label').forEach(lbl=>{
      lbl.addEventListener('click', ()=>{
        form.querySelectorAll('label').forEach(l=>l.classList.remove('selected'));
        lbl.classList.add('selected');
      });
    });
    form.addEventListener('submit', function(){
      // disable submit briefly to prevent double-submit
      const btn = form.querySelector('button[type=submit]'); if (btn) btn.disabled = true; 
      form.classList.add('submitting');
    });
  });

  // Drag & Drop: map items to targets and serialize mapping to hidden input
  document.querySelectorAll('form[data-etype="dragdrop"]').forEach(form=>{
    const mappingInputName = 'mapping';
    let mappingInput = form.querySelector('input[name="'+mappingInputName+'"]');
    if (!mappingInput){ mappingInput = document.createElement('input'); mappingInput.type='hidden'; mappingInput.name=mappingInputName; mappingInput.value = '{}'; form.appendChild(mappingInput); }
    else { mappingInput.value = mappingInput.value || '{}'; }

    // helper to recompute mapping from DOM (target -> [itemIds])
    function updateMapping(){
      const out = {};
      form.querySelectorAll('.drop-target').forEach(target=>{
        const tId = target.getAttribute('data-target-id');
        const items = Array.from(target.querySelectorAll('.drag-item')).map(it=>it.getAttribute('data-item-id'));
        if (items.length) out[tId] = items;
      });
      mappingInput.value = JSON.stringify(out);
    }

    form.querySelectorAll('.drag-item').forEach(item=>{
      item.addEventListener('dragstart', e=>{ e.dataTransfer.setData('text/plain', item.getAttribute('data-item-id')); item.classList.add('dragging'); });
      item.addEventListener('dragend', ()=>{ item.classList.remove('dragging'); updateMapping(); });
    });
    form.querySelectorAll('.drop-target').forEach(target=>{
      target.addEventListener('dragover', e=>{ e.preventDefault(); target.classList.add('drop-over'); });
      target.addEventListener('dragleave', ()=>{ target.classList.remove('drop-over'); });
      target.addEventListener('drop', e=>{
        e.preventDefault(); target.classList.remove('drop-over');
        const itemId = e.dataTransfer.getData('text/plain');
        if (!itemId) return;
        // move DOM element into target (allow multiple items)
        const itemEl = form.querySelector('.drag-item[data-item-id="'+itemId+'"]');
        if (itemEl){
          target.appendChild(itemEl);
          itemEl.style.cursor = 'default'; itemEl.draggable = false;
          updateMapping();
        }
      });
    });
    // ensure mapping is up-to-date on submit
    form.addEventListener('submit', ()=>{
      updateMapping();
      const btn = form.querySelector('button[type=submit]'); if (btn) btn.disabled = true;
    });
    // initialize mapping in case items were pre-placed server-side
    updateMapping();
  });

  // If server printed a result message like 'Score: x / y', show quick toast and success sound
  const resultEl = document.querySelector('p.muted strong');
  if (resultEl && resultEl.textContent.trim().startsWith('Score:')){
    const t = document.createElement('div'); t.className='toast card'; t.textContent = resultEl.textContent.trim(); document.body.appendChild(t);
    try{ window.playSuccess(); }catch(e){}
    setTimeout(()=>t.remove(),2500);
  }

});

/* small helper: add subtle selection style via CSS injection in case missing */
const style = document.createElement('style'); style.innerHTML = '\nlabel.selected{background:linear-gradient(90deg, rgba(61,138,247,0.08), rgba(255,183,77,0.03));border-color:rgba(61,138,247,0.12);} \nform.submitting{opacity:0.6}'; document.head.appendChild(style);
