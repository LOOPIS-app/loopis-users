document.addEventListener('DOMContentLoaded', () => {
const ta = document.getElementById('comment');

if (!ta || !window.loopisUserData) {
  return;
}
let menu = document.getElementById('loopis-mention-menu');

if (!menu) {
  menu = document.createElement('ul');
  menu.id = 'loopis-mention-menu';
  document.body.appendChild(menu);
}

  let suggestions = [];
  let triggerIndex = -1;

  ta.addEventListener('click', onInput);
  ta.addEventListener('input', onInput);
  ta.addEventListener('keydown', onKey);

  menu.addEventListener('pointerdown', (event) => {
    const li = event.target.closest('li');
    if (li) {
      event.preventDefault();
      choose(li);
    }
  });

  loadSuggestions();

  async function loadSuggestions() {
    try {
      const response = await fetch(loopisUserData.restUrl, {
        method: 'GET',
        headers: {
          'X-WP-Nonce': loopisUserData.nonce,
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
      });

      if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
      }

      const users = await response.json();
      suggestions = Array.isArray(users)
        ? users
            .filter(
              (user) =>
                typeof user.login === 'string' &&
                typeof user.display_name === 'string'
            )
            .map((user) => ({
              login: user.login,
              displayName: user.display_name,
            }))
        : [];
        suggestions.forEach((user)=>{console.log(user.displayName)});
    } catch (error) {
      console.error('Could not load user logins:', error);
      suggestions = [];
    }
  }

  function getQuery(text, pos) {
    const before = text.slice(0, pos);
    const match = before.match(/@([\p{L}\p{N}\p{M}_.-]*)$/u);

    if (!match) {
      return null;
    }

    const start = pos - match[1].length - 1;
    if (start > 0 && /[\p{L}\p{N}\p{M}_.-]/u.test(text[start - 1])) {
      return null;
    }

    return { query: match[1], start };
  }

  function getCaretCoordinates(textarea, position) {
    const style = getComputedStyle(textarea);
    const rect = textarea.getBoundingClientRect();
    const mirror = document.createElement('div');

    for (const prop of [
      'font', 'letterSpacing', 'lineHeight', 'textAlign', 'textIndent',
      'textTransform', 'wordSpacing', 'padding', 'border', 'boxSizing',
      'whiteSpace', 'overflowWrap',
    ]) {
      mirror.style[prop] = style[prop];
    }

    Object.assign(mirror.style, {
      position: 'fixed',
      left: `${rect.left}px`,
      top: `${rect.top}px`,
      width: `${textarea.clientWidth}px`,
      height: `${textarea.clientHeight}px`,
      visibility: 'hidden',
      overflow: 'hidden',
    });

    mirror.textContent = textarea.value.slice(0, position);

    const marker = document.createElement('span');
    marker.textContent = '\u200b';
    mirror.appendChild(marker);
    document.body.appendChild(mirror);

    mirror.scrollTop = textarea.scrollTop;
    mirror.scrollLeft = textarea.scrollLeft;

    const caret = marker.getBoundingClientRect();
    mirror.remove();

    return { left: caret.left, top: caret.top };
  }
function showMenu(items, x, y) {
  menu.replaceChildren();

  items.forEach((user, index) => {
    const li = document.createElement('li');
    li.dataset.index = String(index);
    li.dataset.login = user.login;
    li.textContent = user.displayName;
    menu.appendChild(li);
  });

  if (!items.length) {
    menu.style.display = 'none';
    return;
  }

  menu.style.display = 'block';

  const viewport = window.visualViewport;
  const leftEdge = viewport?.offsetLeft ?? 0;
  const topEdge = viewport?.offsetTop ?? 0;
  const rightEdge = leftEdge + (viewport?.width ?? window.innerWidth);
  const bottomEdge = topEdge + (viewport?.height ?? window.innerHeight);
  const margin = 8;

  const rect = menu.getBoundingClientRect();

  const left = Math.max(
    leftEdge + margin,
    Math.min(x, rightEdge - rect.width - margin)
  );

  let top = y + 20;
  if (top + rect.height > bottomEdge - margin) {
    top = y - rect.height;
  }

  top = Math.max(
    topEdge + margin,
    Math.min(top, bottomEdge - rect.height - margin)
  );

  menu.style.left = `${left}px`;
  menu.style.top = `${top}px`;
}

  function hideMenu() {
    menu.style.display = 'none';
    triggerIndex = -1;
  }

  function onInput() {
    const position = ta.selectionStart;
    const result = getQuery(ta.value, position);

    if (!result) {
      hideMenu();
      return;
    }

    triggerIndex = result.start;

    const query = result.query.toLowerCase();
    const normalize = (value) => value.trim().normalize("NFC").toLowerCase();
    const normalizedQuery = normalize(query);
    
    let matches = suggestions
      .filter((user) => normalize(user.displayName).startsWith(normalizedQuery))
      .slice(0, 10);

    if (matches.length === 0) {
      matches = suggestions
        .filter((user) =>
          normalize(user.displayName)
            .split(/\s+/)
            .slice(1)
            .some((part) => part.startsWith(normalizedQuery))
        )
        .slice(0, 10);
    }
    
    const caret = getCaretCoordinates(ta, position);
    showMenu(matches, caret.left, caret.top);
  }

  function onKey(event) {
    if (menu.style.display === 'none') {
      return;
    }

    const items = Array.from(menu.children);
    if (!items.length) {
      return;
    }

    const selectedIndex = items.findIndex((item) =>
      item.classList.contains('sel')
    );
    const current = selectedIndex === -1 ? 0 : selectedIndex;

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();

      const direction = event.key === 'ArrowDown' ? 1 : -1;
      const next = Math.max(0, Math.min(items.length - 1, current + direction));

      items.forEach((item) => item.classList.remove('sel'));
      items[next].classList.add('sel');
    } else if (event.key === 'Enter' && menu.style.display !== 'none') {
      event.preventDefault();
      choose(items[current]);
    } else if (event.key === 'Escape') {
      hideMenu();
    }
  }

  function choose(li) {
    if (!li || triggerIndex < 0) {
      return;
    }

    const value = li.dataset.login;
    const before = ta.value.slice(0, triggerIndex);
    const after = ta.value.slice(ta.selectionStart);
    const token = `@${value} `;

    ta.value = before + token + after;

    const newPosition = (before + token).length;
    ta.setSelectionRange(newPosition, newPosition);
    ta.focus();
    hideMenu();
  }
});
