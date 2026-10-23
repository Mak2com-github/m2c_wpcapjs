(function() {
  var DEBUG = false;

  var log = function() {
    if (DEBUG) {
      console.log.apply(console, arguments);
    }
  };

  var config = window.capjsCustomConfig || {};
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

  function translateElement(element) {
    translateTree(element);

    if (element.shadowRoot) {
      translateTree(element.shadowRoot);

      var shadowObserver = new MutationObserver(function() {
        translateTree(element.shadowRoot);
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
