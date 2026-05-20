document.addEventListener('DOMContentLoaded', function(){
  // MCQ validation
  document.querySelectorAll('form').forEach(form=>{
    form.addEventListener('submit', function(e){
      if (form.datasetetype === 'mcq' || form.querySelector('input[type=radio]')){
        const radios = form.querySelectorAll('input[type=radio]');
        if (radios.length && !Array.from(radios).some(r=>r.checked)){
          e.preventDefault(); alert('Please select an answer before submitting.');
        }
      }
    });
  });

  // Setup drag & drop areas
  document.querySelectorAll('.drag-item').forEach(item=>{
    item.addEventListener('dragstart', function(ev){
      ev.dataTransfer.setData('text/plain', ev.target.dataset.itemId);
    });
  });
  document.querySelectorAll('.drop-target').forEach(zone=>{
    zone.addEventListener('dragover', function(ev){ ev.preventDefault(); });
    zone.addEventListener('drop', function(ev){
      ev.preventDefault();
      const itemId = ev.dataTransfer.getData('text/plain');
      const item = document.querySelector('.drag-item[data-item-id="'+itemId+'"]');
      if (!item) return;
      // move item into target
      ev.target.appendChild(item);
      // mark data
      updateMapping(ev.target.closest('form'));
    });
  });

  function updateMapping(form){
    if (!form) return;
    const mapping = {};
    form.querySelectorAll('.drop-target').forEach(t=>{
      const targetId = t.dataset.targetId;
      const child = t.querySelector('.drag-item');
      if (child) mapping[targetId] = child.dataset.itemId;
    });
    let hidden = form.querySelector('input[name="mapping"]');
    if (!hidden){ hidden = document.createElement('input'); hidden.type='hidden'; hidden.name='mapping'; form.appendChild(hidden); }
    hidden.value = JSON.stringify(mapping);
  }

  // Initialize existing forms mapping
  document.querySelectorAll('form').forEach(f=>updateMapping(f));
});
