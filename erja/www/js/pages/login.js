$(function() {
    var $loginBtn = $('#loginBtn'),
        $registerBtn = $('#registerBtn');
    if (localStorage.getItem("preEmail") === null) {
        var mainmenu = $("#frontUserAction"),
            loginForm = $("#loginForm"),
            registerForm = $("#registerForm");
        mainmenu.show();
        $loginBtn.on('click', function(){
            var username = $("#username").val().trim(),
                password = $("#password").val().trim();
            if (!username.length) {
                navigator.notification.alert("Anna käyttäjänimi", '', 'ERJA :: Kirjautumisesi epäonnistui', 'Jatka');
            } else if (!password.length) {
                navigator.notification.alert("Anna salasana", '', 'ERJA :: Kirjautumisesi epäonnistui', 'Jatka');
            } else {
                $loader.fadeIn().find("p").text("Kirjaudutaan sisään");
                var signing = login(username, password);
                signing.done(function(response) {
                    var response = JSON.parse(response);
                    response.status ? loginSuccess(response.data) : loginFialed(response.error);
                    $loader.fadeOut();
                });
            }
        });
        $registerBtn.on('click', function(){

        });
        $("#loginWrapperBtn").on("click", function() {
            mainmenu.hide();
            loginForm.fadeIn();
        });
        $("#registerWrapperBtn").on("click", function() {
            mainmenu.hide();
            registerForm.fadeIn();
        });
        $(".backtoMainMenu").on("click", function() {
            loginForm.hide();
            registerForm.hide();
            mainmenu.fadeIn();
        });
    } else {
        redirect("activation.html");
    }
});

function login(username, password) {
    return $.post(ajaxUrl, {
        function: "user",
        action: "login",
        username: username,
        password: password,
    });
}

function register() {
    var email = $("#email").val().trim();
    if (!email.length) {
        navigator.notification.alert("Anna sähköpostiosoitteesi", registerFailed, 'ERJA :: Rekisteröintisi epäonnistui', 'Jatka');
        return;
    }
    $.post(ajaxUrl, {
        function: "user",
        action: "register",
        email: email
    }, function(data) {
            if (data == "inuse") {
                registerFailed(data);
            } else if (data == "registered") {
                registerSuccess(email);
            }
    });
}

function loginSuccess(data) {
    window.localStorage.setItem('account', JSON.stringify(data.user));
    window.localStorage.setItem('pagelist', data.pagelist);
    redirect('index.html');
}

function loginFailed(error) {
    var errorMsg,
        errorType;
    if (error == "wrongpass") {
        errorMsg = "Käyttäjänimi tai salasana on väärin";
        errorType = "Sovellusilmoitus";
    } else if (error == "nouser") {
        errorMsg = "Käyttäjänimeä ei löydy";
        errorType = "Sovellusilmoitus";
    } else if (error == "unconfirmed") {
        errorMsg = "Tiliäsi ei ole vielä aktivoitu. Aktivoi tilisi painamalla aktivointilinkkiä sähköpostistasi";
        errorType = "Sovellusilmoitus";
    } else {
        errorMsg = "Kokeile uudestaan";
        errorType = "Sovellusvirhe";
    }
    navigator.notification.alert(errorMsg, '', errorType, 'OK');
}
  