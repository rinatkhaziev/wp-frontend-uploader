jQuery(function ($) {
  // Drop in _.string
  _.mixin(s.exports());
  var uploadForm = $('.fu-upload-form');
  var uploadFormInputs = uploadForm.find('input[type="text"], textarea');
  _.each( uploadForm, function (f, i) {
    $(f).validate({
      submitHandler: function (form) {
        form.submit();
      }
    })
  });

  /**
   * Only set the fields if an error happened
   * @return {[type]} [description]
   */
  var shouldPopulate = function () {
    var qv = getQueryVariable('response');
    return false === (qv == '' || qv == 'fu-sent' || qv == 'fu-post-sent');
  };

  /**
   * Extract needed var from location.search
   * @param  {[type]} variable [description]
   * @return {[type]}          [description]
   */
  var getQueryVariable = function (variable) {
    var query = window.location.search.substring(1);
    var vars = query.split('&');
    for (var i = 0; i < vars.length; i++) {
      var pair = vars[i].split('=');
      if (decodeURIComponent(pair[0]) == variable) {
        return decodeURIComponent(pair[1]);
      }
    }

    return '';
  }

  // Strip all tags and escape HTML
  var sanitize = function (str) {
    return _.escapeHTML(_.stripTags(str));
  };

  /**
   * Iterate over form text inputs and textareas and either populate value or remove it from local storage
   * @param  {[type]} value [description]
   * @param  {[type]} key   [description]
   * @param  {[type]} list
   * @return {[type]}       [description]
   */
  _.each(uploadFormInputs, function (value, key, list) {
    var lsKey = $(value).prop('id') + ':value';
    if (shouldPopulate()) {
      $(value).val(sanitize(localStorage.getItem(lsKey)));
    } else {
      localStorage.removeItem(lsKey);
    }
  });

  /**
   * Store input value in localStorage
   */
  uploadFormInputs.on('change keyup focusin focusout', function (e) {
    var el = $(e.target);

    var key = el.prop('id') + ':value';

    // you can never be too safe
    var val = sanitize(el.val());

    localStorage.setItem(key, val);
  });

});