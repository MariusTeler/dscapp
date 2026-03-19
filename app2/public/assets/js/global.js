function ajaxLogin() {
    $('#parola_expirata_popup').hide();
    $.ajax(HTTP, {
        type: "POST",
        data: {login_user:$('#login_user').val(),login_pass:$('#login_pass').val(), fromModal:1},
        statusCode: {
            200: function (response) {
                if(!($.modal === undefined) && $.modal.isActive()){
                    $.modal.close();
                    window.location = '/';
                }
            },
            401: function (response) {
                if(!($.modal === undefined) && !$.modal.isActive()) {
                    $('#LoginModal').modal();
                }
            },
            409: function (response) {
                if(!($.modal === undefined) && !$.modal.isActive()) {
                    $('#LoginModal').modal();
                }
                $('#buton_logare_popup').hide();
                $('#parola_expirata_popup').show();
            }
        }
    });
}