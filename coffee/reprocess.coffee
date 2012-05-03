$(document).ready ->
    pull = (value) -> $('input[name=' + value + ']').val()

    sections = -> $('input[name^=section_]')

    courses = -> $('input[name^=course_]')

    $('form[method=POST]').submit ->
        params = {
            type: pull 'type',
            id: pull 'id'
        }

        set = (section) ->
            name = $(section).attr 'name'
            params[name] = pull name

        set elem for elem in courses()
        set elem for elem in sections()

        loading = $('#loading').html()
        $('.buttons').html loading

        $.post 'rpc.php', params, (data) ->
            $('#notice').html data

        false
