(function() {
  console.log('[CapJS Custom] Script de traduction chargé');

  function replaceText(element) {
    // Vérifier le Shadow DOM si présent
    if (element.shadowRoot) {
      console.log('[CapJS Custom] Shadow DOM détecté');
      const walker = document.createTreeWalker(
        element.shadowRoot,
        NodeFilter.SHOW_TEXT,
        null,
        false
      );

      let node;
      while (node = walker.nextNode()) {
        if (node.textContent.includes("I'm a human")) {
          console.log('[CapJS Custom] Texte trouvé dans Shadow DOM, remplacement...');
          node.textContent = node.textContent.replace("I'm a human", "Je suis un humain");
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
        console.log('[CapJS Custom] Texte trouvé dans DOM normal, remplacement...');
        node.textContent = node.textContent.replace("I'm a human", "Je suis un humain");
      }
    }

    // Vérifier aussi les labels et spans spécifiquement
    const labels = element.querySelectorAll('label, span, div');
    labels.forEach(function(label) {
      if (label.textContent && label.textContent.includes("I'm a human")) {
        console.log('[CapJS Custom] Texte trouvé dans label/span, remplacement...');
        label.textContent = label.textContent.replace("I'm a human", "Je suis un humain");
      }
    });
  }

  function translateWidget() {
    console.log('[CapJS Custom] Recherche de widgets CapJS...');

    // Utiliser MutationObserver pour détecter les changements
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        mutation.addedNodes.forEach(function(node) {
          if (node.nodeType === 1) { // Element node
            if (node.tagName === 'CAP-WIDGET' || node.matches('[class*="cap"], [id*="cap"]')) {
              console.log('[CapJS Custom] Widget détecté via MutationObserver');
              setTimeout(function() { replaceText(node); }, 100);
            }
            // Chercher dans les enfants
            const widgets = node.querySelectorAll('cap-widget, [class*="cap"], [id*="cap"]');
            widgets.forEach(function(widget) {
              console.log('[CapJS Custom] Widget enfant détecté');
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

    function checkAndReplace() {
      attempts++;
      console.log('[CapJS Custom] Tentative ' + attempts + '/' + maxAttempts);

      const elements = document.querySelectorAll('cap-widget, [class*="cap"], [id*="cap"], .capjs-widget-container');

      if (elements.length > 0) {
        console.log('[CapJS Custom] ' + elements.length + ' éléments trouvés');
        elements.forEach(replaceText);
      } else {
        console.log('[CapJS Custom] Aucun élément trouvé');
      }

      if (attempts < maxAttempts) {
        setTimeout(checkAndReplace, 500);
      } else {
        console.log('[CapJS Custom] Arrêt des tentatives');
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
