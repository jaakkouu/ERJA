$(function() {
    if (window.localStorage.getItem("preEmail") === null) {
        redirect("login.html");
    } else {
        $("#preEmail").text(window.localStorage.getItem('preEmail'));
        isAccountConfirmed();
    }
});

function isAccountConfirmed() {
    $.get(ajaxUrl, {
        function: "user",
        action: "isAccountConfirmed",
        email: window.localStorage.getItem('preEmail')
    }, function(data) {
        if (data == 1) {
            $("#activationStatus").text("Aktivoitu").css("color", "green");
            stopChecking();
        } else if (data == 0) {
            $("#activationStatus").text("Ei aktivoitu").css("color", "red");
            setTimeout(function() {
                isAccountConfirmed();
            }, 15000);
        }
    });
}

function stopChecking() {
    setTimeout(function() {
        window.localStorage.removeItem('preEmail');
        redirect("login.html");
    }, 3000);
}