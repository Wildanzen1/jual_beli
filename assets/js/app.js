document.querySelectorAll('[data-confirm]').forEach(el=>el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm||'Yakin ingin melanjutkan?'))e.preventDefault()}));
document.querySelectorAll('.alert').forEach(el=>setTimeout(()=>{el.style.opacity='0';},4000));
document.querySelectorAll('[data-table-search]').forEach(input=>input.addEventListener('input',()=>{const table=input.closest('.panel').querySelector('tbody');const q=input.value.toLowerCase();table.querySelectorAll('tr').forEach(row=>row.style.display=row.innerText.toLowerCase().includes(q)?'':'none')}));
