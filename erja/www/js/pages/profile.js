$(function() {
    $("#user_firstname").val(getStringFromLocalStorage("account", "firstname"));
    $("#user_lastname").val(getStringFromLocalStorage("account", "lastname"));
    $("input[name='user_sex'][value='" + getStringFromLocalStorage("account", "sex") + "']").prop("checked", true).parent().addClass("selected");
    $("input[name='user_activity'][value='" + getStringFromLocalStorage("account", "activity") + "']").prop("checked", true).parent().addClass("selected");
    $("#user_email").val(getStringFromLocalStorage("account", "email"));
    if (getStringFromLocalStorage("account", "profile_image") !== null) {
        $("#avatar_area").attr("src", getStringFromLocalStorage("account", "profile_image"));
    }
    setYears();
    var getGroupName = getGroupNameByUserId(getStringFromLocalStorage("account", "id")),
        $submitChangesBtn = $('#submitChanges');
    getGroupName.done(function(data) {
        if (data) {
            $("#user_group").show().find('label').html('').append(
                "Olet ryhmässä",
                $('<br />'),
                $('<span />').css('color', '#B10DC9').text(data)
            );
        }
    });

    $submitChangesBtn.on('click', function() {
        var firstname = $("input[name='user_firstname']").val().trim(),
            lastname = $("input[name='user_lastname']").val().trim(),
            birthyear = $("select[name='user_birthyear']").val(),
            sex = $("input[name='user_sex']:checked").val(),
            activity = $("input[name='user_activity']:checked").val();
        $loader.fadeIn().find("p").text("Tallennetaan tietoja...");
        var submitting = submitUserChanges({
            firstname: firstname,
            lastname: lastname,
            birthyear: birthyear,
            sex: sex,
            activity: activity
        });
        submitting.done($loader.fadeOut());
    });
});

function setYears() {
    var year, x = 0;
    for (var i = 0; i < 119; i++) {
        if (i < 10) {
            i = "0" + i;
        }
        year = "19" + i;
        if (i > 99) {
            if (x > 9) {
                year = "20" + x;
            } else {
                year = "200" + x;
            }
            x++;
        }
        $("#user_birthyear").append(
            $("<option />").val(year).text(year)
        )
    }
    $("#user_birthyear").val(getStringFromLocalStorage("account", "birthyear"));
}

function submitUserChanges(user) {
    user.user_id = getStringFromLocalStorage("account", "id");
    user.username = getStringFromLocalStorage("account", "username");
    return $.post(ajaxUrl, {
        data: {
            function: "user",
            action: "submitUserChanges",
            user: user
        }
    });
}

