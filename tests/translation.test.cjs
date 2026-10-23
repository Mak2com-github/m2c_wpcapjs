// Régression du traducteur de secours avec un DOM minimal et sans dépendance.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/capjs-custom.js'), 'utf8');

function fixture(text, config) {
  let value = text, writes = 0;
  const node = { get textContent() { return value; }, set textContent(v) { writes++; value = v; } };
  const shadowRoot = { nodes: [node] };
  const widget = { nodes: [], shadowRoot };
  const observers = [];
  class MutationObserver {
    constructor(callback) { this.callback = callback; observers.push(this); }
    observe(target) { this.target = target; }
  }
  const document = {
    readyState: 'complete', body: {}, querySelectorAll: () => [widget],
    createTreeWalker(root) { let i = 0; return { nextNode: () => root.nodes[i++] || null }; }
  };
  vm.runInNewContext(source, { window: { capjsCustomConfig: config }, document, MutationObserver, NodeFilter: { SHOW_TEXT: 4 }, setTimeout() {}, console });
  return {
    value: () => value, writes: () => writes,
    mutate(v) { value = v; for (const observer of [...observers]) if (observer.target === shadowRoot) observer.callback([]); }
  };
}

test('traduction espagnole initiale et changements des états', () => {
  const f = fixture("I'm a human", { humanLabel: 'Soy humano', verificationComplete: 'Verificación completada', verificationFailed: 'Error de verificación' });
  assert.equal(f.value(), 'Soy humano');
  assert.equal(f.writes(), 1);
  f.mutate('Verification complete'); assert.equal(f.value(), 'Verificación completada');
  f.mutate('Verification failed'); assert.equal(f.value(), 'Error de verificación');
  f.mutate('Soy humano'); assert.equal(f.writes(), 3);
});
test('traduction identique : aucune écriture ni boucle de mutations', () => {
  const f = fixture("I'm a human", { humanLabel: "I'm a human", verificationComplete: 'Verification complete' });
  assert.equal(f.writes(), 0);
  f.mutate('Verification complete'); assert.equal(f.writes(), 0);
});
test('absence de configuration : préserver les textes du composant', () => {
  const f = fixture("I'm a human", undefined);
  assert.equal(f.value(), "I'm a human"); assert.equal(f.writes(), 0);
});
test('texte déjà traduit : aucune réécriture', () => {
  const f = fixture('Soy humano', { humanLabel: 'Soy humano' });
  assert.equal(f.value(), 'Soy humano'); assert.equal(f.writes(), 0);
});
