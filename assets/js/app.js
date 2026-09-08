function openModal(){document.getElementById('studentModal').classList.add('show')}
function closeModal(){let m=document.getElementById('studentModal');if(m)m.classList.remove('show')}
function filterTable(inputId,tableId){let q=document.getElementById(inputId).value.toLowerCase();document.querySelectorAll('#'+tableId+' tbody tr').forEach(r=>r.style.display=r.innerText.toLowerCase().includes(q)?'':'none')}
document.addEventListener('click',e=>{if(e.target.classList.contains('modal'))closeModal()});