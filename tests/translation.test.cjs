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

// Lien « Secured by Cap » du widget : nom accessible traduit et nouvelle fenêtre signalée.
function creditsFixture(config) {
  const attributes = {};
  let writes = 0;
  const link = {
    getAttribute: (name) => (name in attributes ? attributes[name] : null),
    setAttribute: (name, value) => { writes++; attributes[name] = value; }
  };
  attributes['aria-label'] = 'Secured by Cap'; attributes.title = 'Secured by Cap: The self-hosted CAPTCHA for the modern web.'; attributes.target = '_blank';
  const shadowRoot = { nodes: [], querySelectorAll: (selector) => (selector === 'a.credits' ? [link] : []) };
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
  const window = { capjsCustomConfig: config };
  vm.runInNewContext(source, { window, document, MutationObserver, NodeFilter: { SHOW_TEXT: 4 }, setTimeout() {}, console });
  return { attributes, writes: () => writes, window, mutate() { for (const observer of [...observers]) if (observer.target === shadowRoot) observer.callback([]); } };
}
test('lien Cap : nom accessible et titre traduits, rel sécurisé, target conservé', () => {
  const f = creditsFixture({ creditsLabel: 'Sécurisé par Cap (s’ouvre dans une nouvelle fenêtre)' });
  assert.equal(f.attributes['aria-label'], 'Sécurisé par Cap (s’ouvre dans une nouvelle fenêtre)');
  assert.equal(f.attributes.title, 'Sécurisé par Cap (s’ouvre dans une nouvelle fenêtre)');
  assert.equal(f.attributes.rel, 'noopener noreferrer');
  assert.equal(f.attributes.target, '_blank');
  assert.ok(f.attributes['aria-label'].includes('Cap'), 'le nom accessible contient le texte visible « Cap »');
});
test('lien Cap : valeurs déjà posées, aucune réécriture ni boucle de mutations', () => {
  const f = creditsFixture({ creditsLabel: 'Protegido por Cap (se abre en una ventana nueva)' });
  const writes = f.writes();
  f.mutate(); f.mutate();
  assert.equal(f.writes(), writes);
});
test('lien Cap : le widget qui remet un libellé anglais est corrigé à nouveau', () => {
  const f = creditsFixture({ creditsLabel: 'Sécurisé par Cap (s’ouvre dans une nouvelle fenêtre)' });
  f.attributes['aria-label'] = 'Secured by Cap'; f.mutate();
  assert.equal(f.attributes['aria-label'], 'Sécurisé par Cap (s’ouvre dans une nouvelle fenêtre)');
});
test('lien Cap : sans libellé configuré, le lien du widget reste intact', () => {
  const f = creditsFixture(undefined);
  assert.equal(f.attributes['aria-label'], 'Secured by Cap'); assert.equal(f.writes(), 0);
});
test('adresse de la page jamais transmise à trycap.dev', () => {
  assert.equal(creditsFixture(undefined).window.CAP_DISABLE_WIDGET_REF, true);
});
