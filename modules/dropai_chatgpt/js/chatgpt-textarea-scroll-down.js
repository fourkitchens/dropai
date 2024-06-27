(function (Drupal) {
    Drupal.behaviors.textareaScrollDown = {
      attach: function (context) {
        const textarea = context.querySelector(".form-item-chatgpt-chat textarea");

        if (textarea && !textarea.hasAttribute('data-ajax-concat-form-processed')) {
          textarea.scrollTop = this.scrollHeight;
          textarea.setAttribute('data-ajax-concat-form-processed', true);
        }
      }
    };
})(Drupal);
