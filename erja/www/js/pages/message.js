moment.locale("fi");
$(function() {
    var getGroupStatus = isUserInGroup(),
        getMessages = getMessagesByUserId();
    getGroupStatus.done(function(hasGroup) {
        if (hasGroup) {
            $("#createMessageBtn").show();
            getMessages.done(function(data) {
                var data = JSON.parse(data);
                if (data.length) {
                    setMessages(data);
                } else {
                    $("#noMessages").show();
                }
            });
        } else {
            $("#noGroup").show();
        }
    });
});

function createMessage() {
    var object = {
        title: $("#message_title").val(),
        message: $("#message_description").val()
    };
    if (object['title'].length != "" || object['message'].length != "") {
        $.post(ajaxUrl, {
            function: "message",
            action: "createMessage",
            user_id: getStringFromLocalStorage("account", "id"),
            message: JSON.stringify(object),
        }, function (data) {
            if (data == "created") {
                closeModal(".modal_create_message");
                var getMessages = getMessagesByUserId("false");
                getMessages.done(function (data) {
                    var data = JSON.parse(data);
                    if (data.length) {
                        $("#noMessages").hide();
                        $("#messages").html("");
                        setMessages(data);
                    }
                });
            }
        })
    }
}