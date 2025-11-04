(function() {
  // Mode debug : mettre à true pour activer les logs
  const DEBUG = false;

  const log = function() {
    if (DEBUG) {
      console.log.apply(console, arguments);
    }
  };

  log('[CapJS Custom] Script de traduction chargé');

  // Set pour éviter de traiter plusieurs fois le même élément
  const processedElements = new WeakSet();

  function replaceText(element) {
    // Éviter de traiter plusieurs fois le même élément
    if (processedElements.has(element)) {
      return;
    }
    processedElements.add(element);

    let hasReplaced = false;

    // Vérifier le Shadow DOM si présent
    if (element.shadowRoot) {
      log('[CapJS Custom] Shadow DOM détecté');
      const walker = document.createTreeWalker(
        element.shadowRoot,
        NodeFilter.SHOW_TEXT,
        null,
        false
      );

      let node;
      while (node = walker.nextNode()) {
        if (node.textContent.includes("I'm a human")) {
          node.textContent = node.textContent.replace("I'm a human", "Je suis un humain");
          hasReplaced = true;
        }
      }
    }

    // Vérifier le DOM normal
    const walker = document.createTreeWalker(
      element,
      NodeFilter.SHOW_TEXT,
      null,
      false
    );

    let node;
    while (node = walker.nextNode()) {
      if (node.textContent.includes("I'm a human")) {
        node.textContent = node.textContent.replace("I'm a human", "Je suis un humain");
        hasReplaced = true;
      }
    }

    // Vérifier aussi les labels et spans spécifiquement
    const labels = element.querySelectorAll('label, span, div');
    labels.forEach(function(label) {
      if (label.textContent && label.textContent.includes("I'm a human")) {
        label.textContent = label.textContent.replace("I'm a human", "Je suis un humain");
        hasReplaced = true;
      }
    });

    if (hasReplaced) {
      log('[CapJS Custom] Texte traduit avec succès');
    }
  }

  function translateWidget() {
    log('[CapJS Custom] Recherche de widgets CapJS...');

    // Utiliser MutationObserver pour détecter les changements
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        mutation.addedNodes.forEach(function(node) {
          if (node.nodeType === 1) { // Element node
            if (node.tagName === 'CAP-WIDGET' || node.matches('[class*="cap"], [id*="cap"]')) {
              log('[CapJS Custom] Widget détecté via MutationObserver');
              setTimeout(function() { replaceText(node); }, 100);
            }
            // Chercher dans les enfants
            const widgets = node.querySelectorAll('cap-widget, [class*="cap"], [id*="cap"]');
            widgets.forEach(function(widget) {
              log('[CapJS Custom] Widget enfant détecté');
              setTimeout(function() { replaceText(widget); }, 100);
            });
          }
        });
      });
    });

    // Observer le body
    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

    // Faire plusieurs passes pour s'assurer d'attraper le widget
    let attempts = 0;
    const maxAttempts = 20; // 10 secondes
    let widgetsFound = false;

    function checkAndReplace() {
      attempts++;
      log('[CapJS Custom] Tentative ' + attempts + '/' + maxAttempts);

      const elements = document.querySelectorAll('cap-widget, [class*="cap"], [id*="cap"], .capjs-widget-container');

      if (elements.length > 0) {
        if (!widgetsFound) {
          log('[CapJS Custom] ' + elements.length + ' widgets CapJS trouvés');
          widgetsFound = true;
        }
        elements.forEach(replaceText);
      }

      if (attempts < maxAttempts) {
        setTimeout(checkAndReplace, 500);
      } else {
        log('[CapJS Custom] Arrêt des tentatives');
      }
    }

    checkAndReplace();
  }

  // Initialiser
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', translateWidget);
  } else {
    translateWidget();
  }
})();
