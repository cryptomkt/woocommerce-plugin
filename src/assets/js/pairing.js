/**
 * @license Copyright 2011-2014 cryptomarket Inc., MIT License
 * see https://github.com/cryptomarket/woocommerce-cryptomarket/blob/master/LICENSE
 */

'use strict';

(function ( $ ) {

  $(function () {

    /**
     * Update the API Token helper link on Network selection
    */

    $('#cryptomarket_api_token_form').on('change', '.cryptomarket-pairing__network', function (e) {

      // Helper urls
      var livenet = 'https://cryptomarket.com/api-tokens';
      var testnet = 'https://test.cryptomarket.com/api-tokens';

      if ($('.cryptomarket-pairing__network').val() === 'livenet') {
        $('.cryptomarket-pairing__link').attr('href', livenet).html(livenet);
      } else {
        $('.cryptomarket-pairing__link').attr('href', testnet).html(testnet);
      }

    });

    /**
     * Try to pair with cryptomarket using an entered pairing code
    */
    $('#cryptomarket_api_token_form').on('click', '.cryptomarket-pairing__find', function (e) {

      // Don't submit any forms or follow any links
      e.preventDefault();

      // Hide the pairing code form
      $('.cryptomarket-pairing').hide();
      $('.cryptomarket-pairing').after('<div class="cryptomarket-pairing__loading" style="width: 20em; text-align: center"><img src="'+ajax_loader_url+'"></div>');

      // Attempt the pair with cryptomarket
      $.post(cryptomarketAjax.ajaxurl, {
        'action':       'cryptomarket_pair_code',
        'pairing_code': $('.cryptomarket-pairing__code').val(),
        'network':      $('.cryptomarket-pairing__network').val(),
        'pairNonce':    cryptomarketAjax.pairNonce
      })
      .done(function (data) {

        $('.cryptomarket-pairing__loading').remove();

        // Make sure the data is valid
        if (data && data.sin && data.label) {

          // Set the token values on the template
          $('.cryptomarket-token').removeClass('cryptomarket-token--livenet').removeClass('cryptomarket-token--testnet').addClass('cryptomarket-token--'+data.network);
          $('.cryptomarket-token__token-label').text(data.label);
          $('.cryptomarket-token__token-sin').text(data.sin);

          // Display the token and success notification
          $('.cryptomarket-token').hide().removeClass('cryptomarket-token--hidden').fadeIn(500);
          $('.cryptomarket-pairing__code').val('');
          $('.cryptomarket-pairing__network').val('livenet');
          $('#message').remove();
          $('h2.woo-nav-tab-wrapper').after('<div id="message" class="updated fade"><p><strong>You have been paired with your cryptomarket account!</strong></p></div>');
        }
        // Pairing failed
        else if (data && data.success === false) {
          $('.cryptomarket-pairing').show();
          alert('Unable to pair with cryptomarket.');
        }

      });
    });

    // Revoking Token
    $('#cryptomarket_api_token_form').on('click', '.cryptomarket-token__revoke', function (e) {

      // Don't submit any forms or follow any links
      e.preventDefault();

      if (confirm('Are you sure you want to revoke the token?')) {
        $.post(cryptomarketAjax.ajaxurl, {
          'action': 'cryptomarket_revoke_token',
          'revokeNonce':    cryptomarketAjax.revokeNonce
        })
        .always(function (data) {
          $('.cryptomarket-token').fadeOut(500, function () {
            $('.cryptomarket-pairing').removeClass('.cryptomarket-pairing--hidden').show();
            $('#message').remove();
            $('h2.woo-nav-tab-wrapper').after('<div id="message" class="updated fade"><p><strong>You have revoked your token!</strong></p></div>');
          });
        });
      }

    });

  });

}( jQuery ));
