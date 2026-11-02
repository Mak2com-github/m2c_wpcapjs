(function() {
  var DEBUG = false;

  var log = function() {
    if (DEBUG) {
      console.log.apply(console, arguments);
    }
  };

  var config = window.capjsCustomConfig || {};

  // Le widget transmet l'adresse de la page, le domaine et le référent à trycap.dev quand on clique sur son lien
  // « Cap » : on ne le laisse pas faire (option publique du widget, lue au moment du clic).
  window.CAP_DISABLE_WIDGET_REF = true;

  var TRANSLATIONS = {
    "I'm a human": config.humanLabel || '',
    "Verify you're human": config.humanLabel || '',
    "Verify you are human": config.humanLabel || '',
    "I am human": config.humanLabel || '',
    "Verification complete": config.verificationComplete || '',
    "Verification failed": config.verificationFailed || ''
  };

  function replaceInTextNode(node) {
    var text = node.textContent;
    var changed = false;
    for (var key in TRANSLATIONS) {
      if (TRANSLATIONS[key] && text.indexOf(key) !== -1) {
        text = text.replace(key, TRANSLATIONS[key]);
        changed = true;
      }
    }
    // Une traduction identique ne doit pas relancer les MutationObserver.
    if (changed && text !== node.textContent) {
      node.textContent = text;
      log('[CapJS Custom] Texte traduit:', node.textContent);
    }
  }

  function translateTree(root) {
    var walker = document.createTreeWalker(
      root,
      NodeFilter.SHOW_TEXT,
      null,
      false
    );
    var node;
    while (node = walker.nextNode()) {
      replaceInTextNode(node);
    }
  }

  // Lien « Secured by Cap » du widget : il s'ouvre dans un nouvel onglet sans le dire, et son nom accessible est en
  // anglais. Le nom visible reste « Cap » (contenu dans le nouveau nom, RGAA 6.1 et 13.2). Le widget remet de force
  // l'adresse, le texte et le style du lien, jamais ces attributs.
  function fixCredits(root) {
    if (!config.creditsLabel || !root.querySelectorAll) return;
    var links = root.querySelectorAll('a.credits');
    for (var i = 0; i < links.length; i++) {
      var link = links[i];
      // Pas d'écriture inutile : une valeur identique ne doit pas relancer les MutationObserver.
      if (link.getAttribute('aria-label') !== config.creditsLabel) link.setAttribute('aria-label', config.creditsLabel);
      if (link.getAttribute('title') !== config.creditsLabel) link.setAttribute('title', config.creditsLabel);
      if (link.getAttribute('rel') !== 'noopener noreferrer') link.setAttribute('rel', 'noopener noreferrer');
    }
  }

  function translateElement(element) {
    translateTree(element);

    if (element.shadowRoot) {
      translateTree(element.shadowRoot);
      fixCredits(element.shadowRoot);

      var shadowObserver = new MutationObserver(function() {
        translateTree(element.shadowRoot);
        fixCredits(element.shadowRoot);
      });
      shadowObserver.observe(element.shadowRoot, {
        childList: true,
        subtree: true,
        characterData: true
      });
    }
  }

  function translateAllWidgets() {
    var widgets = document.querySelectorAll('cap-widget');
    for (var i = 0; i < widgets.length; i++) {
      translateElement(widgets[i]);
    }
  }

  function init() {
    log('[CapJS Custom] Initialisation de la traduction');

    translateAllWidgets();

    var observer = new MutationObserver(function(mutations) {
      var needsTranslation = false;
      for (var i = 0; i < mutations.length; i++) {
        var mutation = mutations[i];
        for (var j = 0; j < mutation.addedNodes.length; j++) {
          var node = mutation.addedNodes[j];
          if (node.nodeType === 1) {
            if (node.tagName === 'CAP-WIDGET') {
              translateElement(node);
              needsTranslation = true;
            }
            var nested = node.querySelectorAll && node.querySelectorAll('cap-widget');
            if (nested && nested.length > 0) {
              for (var k = 0; k < nested.length; k++) {
                translateElement(nested[k]);
              }
              needsTranslation = true;
            }
          }
        }
      }
      if (!needsTranslation) {
        translateAllWidgets();
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

    var attempts = 0;
    var maxAttempts = 15;
    function periodicCheck() {
      attempts++;
      translateAllWidgets();
      if (attempts < maxAttempts) {
        setTimeout(periodicCheck, 500);
      }
    }
    periodicCheck();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
