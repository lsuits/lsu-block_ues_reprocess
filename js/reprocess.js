(function(){
  $(document).ready(function() {
    var pull, sections;
    pull = function(value) {
      return $('input[name=' + value + ']').val();
    };
    sections = function() {
      return $('input[name^=section_]');
    };
    return $('form[method=POST]').submit(function() {
      var _a, _b, _c, elem, loading, params, set;
      params = {
        type: pull('type'),
        id: pull('id')
      };
      set = function(section) {
        var name;
        name = $(section).attr('name');
        params[name] = pull(name);
        return params[name];
      };
      _b = sections();
      for (_a = 0, _c = _b.length; _a < _c; _a++) {
        elem = _b[_a];
        set(elem);
      }
      loading = $('#loading').html();
      $('.buttons').html(loading);
      $.post('rpc.php', params, function(data) {
        return $('#notice').html(data);
      });
      return false;
    });
  });
})();
