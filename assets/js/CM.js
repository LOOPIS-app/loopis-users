document.addEventListener('DOMContentLoaded', () => {
const ta = document.getElementById('');
const menu = document.getElementById('menu');
let triggerIndex = -1;
let suggestions = ['Aubert','Aabert','Azubert','Bubert','Chubert','Dubert','Eubert','Fubert']; 

function getQuery(text, pos) {
  const before = text.slice(0, pos);
  const m = before.match(/@([a-zA-Z0-9_]*)$/);
  return m ? { q: m[1], start: pos - m[1].length - 1 } : null;
}

function showMenu(items, x, y) {
  menu.innerHTML = items.map((it,i)=>`<li data-i="${i}" style="padding:4px;cursor:pointer">${it}</li>`).join('');
  menu.style.left = x + 'px'; menu.style.top = y + 'px'; menu.style.display = items.length ? 'block' : 'none';
}

function hideMenu(){ menu.style.display='none'; triggerIndex=-1; }

ta.addEventListener('input', onInput);
ta.addEventListener('keydown', onKey);

function onInput(e){
  const pos = ta.selectionStart;
  const r = getQuery(ta.value, pos);
  if(!r){ hideMenu(); return; }
  const q = r.q.toLowerCase();
  triggerIndex = r.start;
  const matches = suggestions.filter(s => s.toLowerCase().startsWith(q));
  const rect = ta.getBoundingClientRect();
  showMenu(matches, rect.left, rect.bottom );
}

function onKey(e){
  if(menu.style.display==='none') return;
  const items = Array.from(menu.children);
  const selected = menu.querySelector('.sel');
  if(e.key === 'ArrowDown'){ e.preventDefault(); move(1); }
  else if(e.key === 'ArrowUp'){ e.preventDefault(); move(-1); }
  else if(e.key === 'Enter'){ e.preventDefault(); choose(items[selectedIndex()]); }
  else if(e.key === 'Escape'){ hideMenu(); }

  function move(dir){
    let i = selectedIndex();
    i = Math.max(0, Math.min(items.length-1, i + dir));
    items.forEach(it=>it.classList.remove('sel'));
    items[i].classList.add('sel');
  }
  function selectedIndex(){ return items.findIndex(it => it.classList.contains('sel')) || 0; }
}

menu.addEventListener('mousedown', e => {
  const li = e.target.closest('li');
  if(!li) return;
  choose(li);
});

function choose(li){
  const val = li.textContent;
  const before = ta.value.slice(0, triggerIndex);
  const after = ta.value.slice(ta.selectionStart);
  const token = `@${val} `;
  ta.value = before + token + after;
  const newPos = (before + token).length;
  ta.setSelectionRange(newPos, newPos);
  hideMenu();
}
})