(function() {
  var DEBUG = false;

  var log = function() {
    if (DEBUG) {
      console.log.apply(console, arguments);
    }
  };

  var TRANSLATIONS = {
    "I'm a human": "Je suis un humain",
    "Verify you're human": "Vérifiez que vous êtes humain",
    "Verify you are human": "Vérifiez que vous êtes humain",
    "I am human": "Je suis un humain",
    "Verification complete": "Vérification terminée",
    "Verification failed": "Échec de la vérification"
  };

  function replaceInTextNode(node) {
    var text = node.textContent;
    var changed = false;
    for (var key in TRANSLATIONS) {
      if (text.indexOf(key) !== -1) {
        text = text.replace(key, TRANSLATIONS[key]);
        changed = true;
      }
    }
    if (changed) {
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
