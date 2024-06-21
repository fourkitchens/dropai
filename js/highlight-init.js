(function (Drupal) {
  Drupal.behaviors.initHighlightJs = {
    attach: function (context, settings) {
      // Highlight code blocks if hljs is available
      if (typeof hljs !== 'undefined') {
        hljs.highlightAll();
      }
    }
  };
})(Drupal);
